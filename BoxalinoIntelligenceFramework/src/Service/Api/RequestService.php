<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api;

use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\FacetDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\FilterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\HeaderParameterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\UserParameterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\ParameterFactory;
use Boxalino\IntelligenceFramework\Service\Api\RequestFactory;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\SortingDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Util\Configuration;
use GuzzleHttp\Client;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class RequestService
{

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ParameterFactory
     */
    protected $parameterFactory;

    protected $context;
    protected $request;

    public function __construct(
        RequestFactory $requestFactory,
        ParameterFactory $parameterFactory,
        Configuration $configuration,
        LoggerInterface $boxalinoLogger
    ){
        $this->context = null;
        $this->request = null;
        $this->parameterFactory = $parameterFactory;
        $this->logger = $boxalinoLogger;
        $this->configuration = $configuration;
        $this->requestFactory = $requestFactory;
    }

    public function get() : string
    {
        $this->requestFactory->setUsername($this->configuration->getUsername())
            ->setApiKey($this->configuration->getApiKey())
            ->setApiSecret($this->configuration->getApiSecret())
            ->setDev($this->configuration->getIsDev())
            ->setTest($this->configuration->getIsTest())
            ->setSessionId("1234567uikjhytrewsdgh5yjhgfe")
            ->setProfileId("234567iuytrewqwer")
            ->setCustomerId("2")
            ->setWidget("search")
            ->setLanguage("de")
            ->setHitCount(10)
            ->setOffset(0)
            ->setGroupBy("id")
            ->setReturnFields(["title", "discountedPrice", "products_brand", "products_image"])
            ->addSort($this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_SORT)->add("id"))
            ->addFilters(
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FILTER)->add("products_visibility", [30]),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FILTER)->add("category_id", ["db1ae14a599c47b89be823bff5d4d7a4"]),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FILTER)->addRange("discountedPrice", 0.80, 1500.30)
            )
            ->setOrFilters(false)
            ->addFacets(
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FACET)->addRange('discountedPrice', 0.80, 1500.30),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FACET)->add("products_tag", 5, 1),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FACET)->add("products_brand", 10,1, "1",false, true)
            )
            ->addHeaderParameters(
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER)->add("User-Host", "51.154.196.18"),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER)->add("User-Agent", "DANNEG@DANNEG-WORK CMD LOCALHOST"),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER)->add("User-Referer", "boxalino.com"),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER)->add("User-Url", "localhost")
            )
            ->addParameters(
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_USER)->add("execution", [date("%Y-%m-%d")])
            );

        return $this->requestFactory->jsonSerialize();
    }

}
