<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Context;

use Boxalino\IntelligenceFramework\Service\Api\Request\ContextAbstract;
use Boxalino\IntelligenceFramework\Service\Api\Request\ContextInterface;
use Boxalino\IntelligenceFramework\Service\Api\Request\ParameterFactory;
use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinitionInterface;
use Boxalino\IntelligenceFramework\Service\Api\Response\ResponseDefinition;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autocomplete request
 *
 * The autocomplete request can have facets&filters set; predefined order, etc
 * These are used align response elements (of different types or under different facets)
 *
 * By default, in Shopware6, the autocomplete response is from
 * the route("/suggest", name="frontend.search.suggest", methods={"GET"}, defaults={"XmlHttpRequest"=true})
 *
 * Can be customized to also have facets set/pre-defined. Please consult with Boxalino on more advanced scenarios.
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class Autocomplete extends ContextAbstract
{
    /**
     * @var int
     */
    protected $hitCount = 1;

    /**
     * @var int
     */
    protected $suggestionCount = 0;

    /**
     * Adding autocomplete specific request parameters
     *
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return RequestDefinitionInterface
     */
    public function get(Request $request, SalesChannelContext $salesChannelContext) : RequestDefinitionInterface
    {
        parent::get($request, $salesChannelContext);
        $this->getApiRequest()
            ->setAcQueriesHitCount($this->getSuggestionsCount())
            ->setHitCount($this->getHitCount());

        return $this->getApiRequest();
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return string
     */
    public function getContextNavigationId(Request $request, SalesChannelContext $salesChannelContext): array
    {
        return [$salesChannelContext->getSalesChannel()->getNavigationCategoryId()];
    }

    /**
     * @return int
     */
    public function getContextVisibility() : int
    {
        return ProductVisibilityDefinition::VISIBILITY_SEARCH;
    }

    /**
     * @return array
     */
    public function getReturnFields() : array
    {
        return ["id", "discountedPrice", "products_seo_url", "title", "products_image"];
    }

    /**
     * Set the number of textual suggestions returned as part of the autocomplete response
     *
     * @param int $count
     * @return $this|ContextAbstract
     */
    public function setSuggestionCount(int $count) : ContextAbstract
    {
        $this->suggestionCount = $count;
        return $this;
    }

    /**
     * @return int
     */
    public function getSuggestionsCount() : int
    {
        return $this->suggestionCount;
    }

}
