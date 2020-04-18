<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

use Boxalino\IntelligenceFramework\Service\Api\Request\ContextInterface;
use Boxalino\IntelligenceFramework\Service\Api\Request\ParameterFactory;
use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinitionInterface;
use Boxalino\IntelligenceFramework\Service\Api\Request\RequestTransformer;
use GuzzleHttp\Client;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
abstract class ContextAbstract implements  ContextInterface
{

    /**
     * @var RequestDefinitionInterface
     */
    protected $apiRequest;

    /**
     * @var ParameterFactory
     */
    protected $parameterFactory;

    /**
     * @var RequestTransformer
     */
    protected $requestTransformer;

    /**
     * @var string
     */
    protected $widget;

    /**
     * @var bool
     */
    protected $orFilters = false;

    /**
     * @var int
     */
    protected $hitCount;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $groupBy = "id";

    /**
     * Listing constructor.
     *
     * @param RequestTransformer $requestTransformer
     * @param ParameterFactory $parameterFactory
     */
    public function __construct(
        RequestTransformer $requestTransformer,
        ParameterFactory $parameterFactory
    ) {
        $this->requestTransformer = $requestTransformer;
        $this->parameterFactory = $parameterFactory;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return RequestDefinitionInterface
     */
    public function get(Request $request, SalesChannelContext $salesChannelContext) : RequestDefinitionInterface
    {
        $this->requestTransformer->setRequestDefinition($this->getApiRequest());
        $this->requestTransformer->transform($request, $salesChannelContext);

        $this->setRequestDefinition($this->requestTransformer->getRequestDefinition());
        $this->getApiRequest()
            ->setReturnFields($this->getReturnFields())
            ->setGroupBy($this->getGroupBy())
            ->setWidget($this->getWidget())
            ->addFilters(
                /** @TODO fix category exporter */
                //$this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FILTER)->add("category_id", $this->getContextNavigationId($request, $salesChannelContext)),
                /** matches the Shopware ProductAvailableFilter logic */
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FILTER)->addRange("products_visibility", $this->getContextVisibility(),1000),
                $this->parameterFactory->get(ParameterFactory::BOXALINO_API_REQUEST_PARAMETER_TYPE_FILTER)->add("products_active", [1])
            );

        return $this->getApiRequest();
    }

    abstract function getContextNavigationId(Request $request, SalesChannelContext $salesChannelContext): array;
    abstract function getContextVisibility() : int;
    abstract function getReturnFields() : array;

    /**
     * @param string $widget
     * @return $this
     */
    public function setWidget(string $widget)
    {
        $this->widget = $widget;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWidget() : string
    {
        return $this->widget;
    }

    /**
     * @return RequestDefinitionInterface
     */
    public function getApiRequest() : RequestDefinitionInterface
    {
        return $this->apiRequest;
    }

    /**
     * @param RequestDefinitionInterface $requestDefinition
     * @return $this
     */
    public function setRequestDefinition(RequestDefinitionInterface $requestDefinition)
    {
        $this->apiRequest = $requestDefinition;
        return $this;
    }

    /**
     * @param bool $orFilters
     * @return ContextAbstract
     */
    public function setOrFilters(bool $orFilters) : self
    {
        $this->orFilters = $orFilters;
        return $this;
    }

    /**
     * @return bool
     */
    public function getOrFilters(): bool
    {
        return $this->orFilters;
    }

    /**
     * @param string $groupBy
     * @return ContextAbstract
     */
    public function setGroupBy(string $groupBy) : self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    public function getGroupBy() : string
    {
        return $this->groupBy;
    }

    /**
     * @return int
     */
    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    /**
     * @param int $hitCount
     * @return ContextAbstract
     */
    public function setHitCount(int $hitCount) : self
    {
        $this->hitCount = $hitCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return ContextAbstract
     */
    public function setOffset(int $offset) : self
    {
        $this->offset = $offset;
        return $this;
    }

}
