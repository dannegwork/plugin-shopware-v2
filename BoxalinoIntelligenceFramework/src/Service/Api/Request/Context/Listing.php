<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Context;

use Boxalino\IntelligenceFramework\Service\Api\Request\ContextAbstract;
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
 * Listing request
 *
 * A listing context can render a default Category/Search view layout:
 * facets, products, sorting, pagination and other narrative elements
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class Listing extends ContextAbstract
{
    const BOXALINO_API_LISTING_NAVIGATION = "navigation";
    const BOXALINO_API_LISTING_SEARCH = "search";

    /**
     * @return int
     */
    public function getContextVisibility() : int
    {
        if($this->getWidget() == self::BOXALINO_API_LISTING_SEARCH)
        {
            return ProductVisibilityDefinition::VISIBILITY_SEARCH;
        }

        return ProductVisibilityDefinition::VISIBILITY_ALL;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return string
     */
    public function getContextNavigationId(Request $request, SalesChannelContext $salesChannelContext): array
    {
        $params = $request->attributes->get('_route_params');
        if ($params && isset($params['navigationId']))
        {
            if(!$this->getWidget())
            {
                $this->setWidget(self::BOXALINO_API_LISTING_NAVIGATION);
            }
            return [$params['navigationId']];
        }

        return [$salesChannelContext->getSalesChannel()->getNavigationCategoryId()];
    }

    /**
     * @return array
     */
    public function getReturnFields() : array
    {
        return ["id", "discountedPrice", "products_seo_url", "title", "products_image"];
    }

}
