<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Response;

use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;
use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandlerInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

interface ResponseDefinitionInterface
{

    /**
     * @return int
     */
    public function getHitCount() : ?int;

    /**
     * @return string|null
     */
    public function getRedirectUrl() : ?string;

    /**
     * @return string|null
     */
    public function getCorrectedSearchQuery() : ?string;

    /**
     * @return string|null
     */
    public function getRequestId() : ?string;

    /**
     * @return string
     */
    public function getGroupBy() : string;

    /**
     * @return string
     */
    public function getVariantId() : string;

    /**
     * @return \ArrayIterator
     */
    public function getBlocks() : \ArrayIterator;

    /**
     * Debug and performance information
     *
     * @return array
     */
    public function getAdvanced() : array;

    /**
     * @return Response
     */
    public function getResponse() : Response;

    /**
     * @param Response $response
     * @return $this
     */
    public function setResponse(Response $response);

    /**
     * @return string
     */
    public function getJson() : string;

    /**
     * @param \StdClass $data
     * @param AccessorInterface $model
     * @return mixed
     */
    public function toObject(\StdClass $data, AccessorInterface $model);

    /**
     * @return AccessorHandlerInterface
     */
    public function getAccessorHandler(): AccessorHandlerInterface;

}
