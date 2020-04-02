<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Test\Api;

use Boxalino\IntelligenceFramework\Service\Api\Util\Configuration;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class RestService
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class RestService
{
    /**
     * @var Client
     */
    private $restClient;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var RequestService
     */
    protected $requestService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Configuration $config,
        RequestService $requestService,
        LoggerInterface $boxalinoLogger
    ){
        $this->restClient = new Client();
        $this->logger = $boxalinoLogger;
        $this->requestService = $requestService;
        $this->config = $config;
    }

    public function request() : string
    {
        $body = $this->requestService->get();
        $request = new Request(
            'POST',
            $this->config->getRestApiEndpoint(),
            ['Content-Type' => 'application/json'],
            $body
        );
        $this->logger->info("====================== body =======================");
        $this->logger->info($body);

        $response = $this->restClient->send($request);

        $jsonResponse = $response->getBody()->getContents();
        $this->logger->info("====================== response =======================");
        $this->logger->info($jsonResponse);

        return $jsonResponse;
    }

}
