<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Util;

use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;

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

}
