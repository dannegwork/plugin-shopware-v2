<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Content\Page;

use Boxalino\IntelligenceFramework\Service\Api\ApiCallServiceInterface;
use Boxalino\IntelligenceFramework\Service\Api\Request\ContextInterface;
use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinitionInterface;
use Boxalino\IntelligenceFramework\Service\Api\Util\Configuration;
use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Content\Product\SalesChannel\Search\ProductSearchGatewayInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Page\StorefrontSearchResult;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AutocompletePageLoader
 * Sample based on a familiar ShopwarePageLoader component
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Content\Page
 */
class ApiPageLoader
{

    /**
     * @var GenericPageLoader
     */
    private $genericLoader;

    /**
     * @var ContextInterface
     */
    private $apiContextInterface;

    /**
     * @var ApiCallServiceInterface
     */
    private $apiCallService;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(
        GenericPageLoader $genericLoader,
        ContextInterface $apiContextInterface,
        ApiCallServiceInterface $apiCallService,
        Configuration $configuration
    ) {
        $this->configuration = $configuration;
        $this->apiCallService = $apiCallService;
        $this->genericLoader = $genericLoader;
        $this->apiContextInterface = $apiContextInterface;
    }

    public function load(Request $request, SalesChannelContext $salesChannelContext): ApiResponsePage
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);
        $page = ApiResponsePage::createFrom($page);

        /**
         * @TODO implement request validators!? ex: search param must exist for search, etc
         */
        if (!$request->query->has('search')) {
            throw new MissingRequestParameterException('search');
        }

        $this->apiCallService->call(
            $this->apiContextInterface->get($request, $salesChannelContext),
            $this->configuration->getRestApiEndpoint($salesChannelContext->getSalesChannel()->getId())
        );

        if($this->apiCallService->getFallback())
        {
            /**
             * @TODO implement fallback scenario (possible parrent call)
             */
        }

        $page->setBlocks($this->apiCallService->getApiResponse()->getBlocks());
        $page->setRequestId("this->apiCallService->getApiResponse()->getRequestId()");
        $page->setGroupBy("this->apiCallService->getApiResponse()->getGroupBy()");

        /**
         * @TODO decide if to set eventDispatches
         */

        return $page;
    }

}
