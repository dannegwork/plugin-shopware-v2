<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Storefront\Controller;

use Boxalino\IntelligenceFramework\Service\Test\Api\RestService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/** @TODO BUILD A REQUESTTRANSFORMER FOR THE API THAT PROCESSES THE REQUEST */
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Storefront\Page\GenericPageLoader;
use TrueBV\Punycode;
use Shopware\Core\SalesChannelRequest;

/**
 * @RouteScope(scopes={"storefront"})
 */
class NarrativeController extends StorefrontController
{

    /**
     * @var RestService
     */
    private $restService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GenericPageLoader
     */
    private $genericLoader;

    /**
     * @var Punycode
     */
    private $punycode;

    public function __construct(
        RestService $restService,
        LoggerInterface $boxalinoLogger,
        GenericPageLoader $genericLoader
    ){
        $this->genericLoader = $genericLoader;
        $this->logger = $boxalinoLogger;
        $this->restService = $restService;
        $this->punycode = new Punycode();
    }

    /**
     * @Route("/narrative/test", name="frontend.narrative.test", options={"seo"="false"}, methods={"GET", "POST"})
     */
    public function test(Request $request, SalesChannelContext $context) : Response
    {
        $content = $this->restService->request();
        $page = $this->genericLoader->load($request, $context);
        return $this->renderStorefront('@BoxalinoIntelligenceFramework/storefront/layout/narrative/test.html.twig', ['content' => $content, 'page'=>$page]);
    }

}
