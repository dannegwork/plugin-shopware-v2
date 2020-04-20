<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api;

use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinitionInterface;
use Boxalino\IntelligenceFramework\Service\Api\Response\ResponseDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Response\ResponseDefinitionInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Class ApiCallService
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class ApiCallService implements ApiCallServiceInterface
{
    /**
     * @var Client
     */
    private $restClient;

    /**
     * @var ResponseDefinition
     */
    protected $apiResponse;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $fallback = false;

    /**
     * @var ResponseDefinitionInterface
     */
    protected $responseDefinition;

    /**
     * ApiCallService constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, ResponseDefinitionInterface $responseDefinition)
    {
        $this->restClient = new Client();
        $this->logger = $logger;
        $this->responseDefinition = $responseDefinition;
    }

    /**
     * @param RequestDefinitionInterface $apiRequest
     * @param string $restApiEndpoint
     * @return ResponseDefinition|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function call(RequestDefinitionInterface $apiRequest, string $restApiEndpoint) : ?ResponseDefinitionInterface
    {
        try {
            $this->setFallback(false);
            $request = new Request(
                'POST',
                $this->getApiEndpoint($restApiEndpoint, $apiRequest->getProfileId()),
                [
                    'Content-Type' => 'application/json'
                ],
                $apiRequest->jsonSerialize()
            );

            $this->logger->info("=============  REQUEST ================");
            $this->logger->info($apiRequest->jsonSerialize());

            $response = $this->restClient->send($request);
            $this->setApiResponse($this->responseDefinition->setResponse($response));

            $this->logger->info("=============  RESPONSE JSON ================");
            $this->logger->info($this->getApiResponse()->getJson());

            return $this->getApiResponse();
        } catch (\Exception $exception)
        {
            $this->setFallback(true);
            $this->logger->error("BoxalinoAPIError: " . $exception->getMessage() . " at " . __CLASS__);
        }

        return null;
    }

    /**
     * @param string $restApiEndpoint
     * @param string $profileId
     * @return string
     */
    public function getApiEndpoint(string $restApiEndpoint, string $profileId)
    {
        return stripslashes($restApiEndpoint) . "?profileId=$profileId";
    }

    /**
     * @return bool
     */
    public function isFallback() : bool
    {
        return $this->fallback;
    }

    /**
     * @param bool $fallback
     * @return $this
     */
    public function setFallback(bool $fallback) : self
    {
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * @return ResponseDefinitionInterface
     */
    public function getApiResponse() : ResponseDefinitionInterface
    {
        return $this->apiResponse;
    }

    /**
     * @param ResponseDefinitionInterface $response
     * @return $this
     */
    public function setApiResponse(ResponseDefinitionInterface $response)
    {
        $this->apiResponse = $response;
        return $this;
    }

}
