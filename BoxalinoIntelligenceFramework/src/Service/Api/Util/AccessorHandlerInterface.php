<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Util;

use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Boxalino\IntelligenceFramework\Service\Api\Util
 */
interface AccessorHandlerInterface
{

    /**
     * @param string $type
     * @param string $setter
     * @param string $modelName
     */
    public function addAccessor(string $type, string $setter, string $modelName);

    /**
     * @param string $accessorType
     * @return AccessorInterface
     */
    public function getAccessor(string $accessorType);

    /**
     * @param string $type
     * @return bool
     */
    public function hasAccessor(string $type) : bool;

    /**
     * @param string $type
     * @return string
     */
    public function getAccessorSetter(string $type) : ?string;

    /**
     * @param string $type
     * @param string $field
     * @return mixed
     */
    public function addHitIdFieldName(string $type, string $field);

    /**
     * @param string $type
     * @return string|null
     */
    public function getHitIdFieldName(string $type) : ?string;

    /**
     * @param string $type
     * @param $context
     * @return mixed
     */
    public function getModel(string $type, $context = null);

    /**
     * @return LoggerInterface | null
     */
    public function getLogger();

}
