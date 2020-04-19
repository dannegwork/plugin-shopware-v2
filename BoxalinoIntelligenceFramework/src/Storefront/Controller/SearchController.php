<?php declare(strict_types=1);

namespace Boxalino\IntelligenceFramework\Storefront\Controller;

use Boxalino\IntelligenceFramework\Service\Api\Content\Page\ApiPageLoader;
use Boxalino\IntelligenceFramework\Service\Api\Request\Context\Autocomplete;
use Boxalino\IntelligenceFramework\Service\Api\Request\Context\Listing;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Shopware\Storefront\Page\GenericPageLoader;
use Shopware\Storefront\Page\Search\SearchPageLoader;
use Shopware\Storefront\Page\Suggest\SuggestPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Storefront\Controller\SearchController as ShopwareSearchController;

/**
 * @RouteScope(scopes={"storefront"})
 */
class SearchController extends ShopwareSearchController
{
    /**
     * @var SearchPageLoader
     */
    private $searchPageLoader;

    /**
     * @var GenericPageLoader
     */
    private $genericLoader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShopwareSearchController
     */
    private $decorated;

    /**
     * @var ApiPageLoader
     */
    private $apiPageLoader;

    /**
     * @var Listing
     */
    private $search;

    public function __construct(
        //ShopwareSearchController $decorated,
        SearchPageLoader $searchPageLoader,
        ApiPageLoader $apiPageLoader,
        Listing $search,
        LoggerInterface $logger
    ){
        //$this->decorated = $decorated;
        $this->searchPageLoader = $searchPageLoader;
        $this->apiPageLoader = $apiPageLoader;
        $this->search = $search;
        $this->logger = $logger;
    }

    /**
     * @HttpCache()
     * @Route("/search", name="frontend.search.page", methods={"GET"})
     */
    public function search(SalesChannelContext $context, Request $request): Response
    {
        try {
            $page = $this->searchPageLoader->load($request, $context);

            $searchResult = $this->search->get($request, $context);
            $this->logger->info("============= SEARCH REQUEST ================");
            $this->logger->info($searchResult->jsonSerialize());

        } catch (MissingRequestParameterException $missingRequestParameterException) {
            return $this->forwardToRoute('frontend.home.page');
        }

        return $this->renderStorefront('@Storefront/storefront/page/search/index.html.twig', ['page' => $page]);
    }

    /**
     *
     * @HttpCache()
     * @Route("/suggest", name="frontend.search.suggest", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     */
    public function suggest(SalesChannelContext $context, Request $request): Response
    {
        #$page = $this->searchPageLoader->load($request, $context);
        $page = $this->apiPageLoader->load($request, $context);

        #return $this->renderStorefront('@Storefront/storefront/layout/header/search-suggest.html.twig', ['page' => $page]);
        return $this->renderStorefront('@BoxalinoIntelligenceFramework/storefront/layout/narrative/main.html.twig', ['page' => $page]);
    }

    /**
     * @HttpCache()
     *
     * Route to load the listing filters
     *
     * @RouteScope(scopes={"storefront"})
     * @Route("/widgets/search/{search}", name="widgets.search.pagelet", methods={"GET", "POST"}, defaults={"XmlHttpRequest"=true})
     *
     * @throws MissingRequestParameterException
     */
    public function pagelet(Request $request, SalesChannelContext $context): Response
    {
        $request->request->set('no-aggregations', true);

        $page = $this->searchPageLoader->load($request, $context);

        $this->logger->info("============= FILTER SEARCH REQUEST ================");

        return $this->renderStorefront('@Storefront/storefront/page/search/search-pagelet.html.twig', ['page' => $page]);
    }

}
