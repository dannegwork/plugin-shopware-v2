<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api;

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

    /**
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(): ?string
    {
        try {
            $request = new Request(
                'POST',
                $this->config->getRestApiEndpoint(),
                ['Content-Type' => 'application/json'],
                $this->requestService->get()
            );
            $response = $this->restClient->send($request);

            return $response->getBody()->getContents();
        } catch (\Exception $exception)
        {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

}
