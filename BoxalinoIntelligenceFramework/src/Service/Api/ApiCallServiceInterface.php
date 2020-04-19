<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api;

use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinitionInterface;
use Boxalino\IntelligenceFramework\Service\Api\Response\ResponseDefinitionInterface;

/**
 * Class ApiCallServiceInterface
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
interface ApiCallServiceInterface
{
    /**
     * Makes an API request to Boxalino, using the RequestDefinitionInterface and rest api endpoint provided
     * If there are errors, sets the fallback to true
     *
     * @param RequestDefinitionInterface $apiRequest
     * @param string $restApiEndpoint
     * @return ResponseDefinitionInterface|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function call(RequestDefinitionInterface $apiRequest, string $restApiEndpoint) : ?ResponseDefinitionInterface;

    /**
     * @return bool
     */
    public function isFallback() : bool;

    /**
     * @return ResponseDefinitionInterface
     */
    public function getApiResponse() : ResponseDefinitionInterface;

}
