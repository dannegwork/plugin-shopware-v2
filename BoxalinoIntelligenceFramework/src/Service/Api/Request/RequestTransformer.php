<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

use Boxalino\IntelligenceFramework\Service\Api\Util\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Class RequestTransformer
 *
 * Adapts the Shopware6 request to a boxalino data contract
 * Sets request variables dependent on the channel
 * (account, credentials, environment details -- language, dev, test, session, header parameters, etc)
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class RequestTransformer
{
    public const BOXALINO_REQUEST_BUILDER_CACHE_KEY_CONFIG = 'boxalino_request_builder_config';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TagAwareAdapterInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var RequestDefinitionInterface
     */
    protected $requestDefinition;

    /**
     * @var ParameterFactory
     */
    protected $parameterFactory;

    /**
     * @var int
     */
    protected $limit = 0;

    /**
     * RequestTransformer constructor.
     * @param Connection $connection
     * @param TagAwareAdapterInterface $cache
     * @param ParameterFactory $parameterFactory
     * @param Configuration $configuration
     * @param LoggerInterface $logger
     */
    public function __construct(
        Connection $connection,
        ParameterFactory $parameterFactory,
        Configuration $configuration,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->configuration = $configuration;
        $this->parameterFactory = $parameterFactory;
        $this->logger = $logger;
    }

    /**
     * Sets context parameters (credentials, server, etc)
     * Adds parameters per request query elements
     *
     * @param Request $request
     * @param SalesChannelContext $context
     * @return RequestDefinitionInterface
     */
    public function transform(Request $request, SalesChannelContext $context): RequestDefinitionInterface
    {
        if(!$this->requestDefinition)
        {
            throw new UnsatisfiedDependencyException("BoxalinoAPI: the RequestDefinitionInterface has not been set on the RequestTransformer");
        }

        $salesChannelId = $context->getSalesChannel()->getId();
        $sessionId = $request->getSession()->getId();
        $customerId = is_null($context->getCustomer()) ? $sessionId : $context->getCustomer()->getId();

        $this->configuration->setChannelId($salesChannelId);
        $this->requestDefinition
            ->setUsername($this->configuration->getUsername($salesChannelId))
            ->setApiKey($this->configuration->getApiKey($salesChannelId))
            ->setApiSecret($this->configuration->getApiSecret($salesChannelId))
            ->setDev($this->configuration->getIsDev($salesChannelId))
            ->setTest($this->configuration->getIsTest($salesChannelId))
            ->setSessionId($sessionId)
            ->setProfileId($customerId)
            ->setCustomerId($customerId)
            ->setLanguage(substr($request->getLocale(), 0, 2));

        $this->addHitCount();
        $this->addOffset(1);
        $this->addParameters($request);

        return $this->requestDefinition;
    }

    /**
     * @param Request $request
     */
    public function addParameters(Request $request) : void
    {
        $this->requestDefinition->addHeaderParameters(
            $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER)->add("User-Host", $request->getClientIp()),
            $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER)->add("User-Agent", $request->headers->get('user-agent')),
            $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER)->add("User-Referer", $request->headers->get('referer')),
            $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER)->add("User-Url", $request->getUri())
        )
            ->addParameters(
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_USER)->add(SalesChannelRequest::ATTRIBUTE_DOMAIN_ID,
                    [$request->attributes->get(SalesChannelRequest::ATTRIBUTE_DOMAIN_ID)]),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_USER)->add(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID,
                    [$request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID)])
            );

        $queryString = $request->getQueryString();
        parse_str($queryString, $params);
        foreach($params as $param => $value)
        {
            if($param == "sort")
            {
                $this->addSort($value);
                continue;
            }

            if($param == 'p')
            {
                $this->addOffset((int)$value);
                continue;
            }

            if($param == 'limit')
            {
                $this->addHitCount($value);
                continue;
            }

            if($param == 'search')
            {
                $this->requestDefinition->setQuery($value);
                continue;
            }

            $value = is_array($value) ? $value : [$value];
            $this->requestDefinition->addParameters(
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_USER)->add($param, $value)
            );
        }
    }

    /**
     * @param string $value
     */
    public function addSort(string $value)
    {
        $hasDirection = strstr($value, "-");
        $direction = '';
        if($hasDirection)
        {
            $direction =  mb_substr(strstr($value, "-"), 1, 4);
        }
        $field = strstr($value, "-", true);
        if($field === 'score')
        {
            return ;
        }
        $reverse = $direction === FieldSorting::DESCENDING ?? false;

        $this->requestDefinition->addSort($this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_SORT)->add($this->getFieldName($field), $reverse));
    }

    /**
     * @param $field
     * @return $this|string
     */
    public function getFieldName($field)
    {
        if(in_array($field, array_keys($this->getSortFields())))
        {
            return $this->getSortFields()[$field];
        }

        return "products_" . $field;
    }

    /**
     * @param int $page
     * @return int
     */
    public function addOffset(int $page = 1) : self
    {
        $this->requestDefinition->setOffset(($page-1) * $this->getLimit());
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit() : int
    {
        return 20;
    }

    /**
     * @param int $hits
     * @return $this
     */
    public function addHitCount(int $hits = 0) : self
    {
        if(!$hits)
        {
            $hits = $this->getLimit();
        }

        $this->requestDefinition->setHitCount($hits);
        return $this;
    }

    /**
     * @param RequestDefinitionInterface $requestDefinition
     * @return $this
     */
    public function setRequestDefinition(RequestDefinitionInterface $requestDefinition)
    {
        $this->requestDefinition = $requestDefinition;
        return $this;
    }

    /**
     * @return RequestDefinitionInterface
     */
    public function getRequestDefinition() : RequestDefinitionInterface
    {
        return $this->requestDefinition;
    }

    /**
     * @return array
     */
    public function getSortFields() : array
    {
        return [
            'name'      => 'products_bx_parent_title',
            'price'     => 'products_bx_grouped_price',
            'id       ' => 'products_group_id'
        ];
    }

    private function fetchConfig(): array
    {
        $item = $this->cache->getItem(self::BOXALINO_REQUEST_BUILDER_CACHE_KEY_CONFIG);

        if ($item->isHit() && $item->get()) {
            return $item->get();
        }

        /** @var Statement $statement */
        $statement = $this->connection->createQueryBuilder()
            ->select(
                [
                    'CONCAT(TRIM(TRAILING "/" FROM domain.url), "/") `key`',
                    'CONCAT(TRIM(TRAILING "/" FROM domain.url), "/") url',
                    'LOWER(HEX(domain.id)) id',
                    'LOWER(HEX(sales_channel.id)) salesChannelId',
                    'LOWER(HEX(theme.id)) themeId',
                    'snippet_set.iso as locale'
                ]
            )->from('system_config')
            ->innerJoin('sales_channel', 'sales_channel_domain', 'domain', 'domain.sales_channel_id = sales_channel.id')
            ->innerJoin('domain', 'snippet_set', 'snippet_set', 'snippet_set.id = domain.snippet_set_id')
            ->leftJoin('sales_channel', 'theme_sales_channel', 'theme_sales_channel', 'sales_channel.id = theme_sales_channel.sales_channel_id')
            ->leftJoin('theme_sales_channel', 'theme', 'theme', 'theme_sales_channel.theme_id = theme.id')
            ->where('sales_channel.type_id = UNHEX(:typeId)')
            ->andWhere('sales_channel.active')
            ->setParameter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT)
            ->execute();

        $config = FetchModeHelper::groupUnique($statement->fetchAll());

        $item->set($config);
        $this->cache->save($item);

        return $config;
    }

}
