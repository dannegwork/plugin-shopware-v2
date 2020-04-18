<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Util;

use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class ResponseAccessor
 *
 * Boxalino system accessors (base)
 * It is updated on further API extension & use-cases availability
 * Can be extended via custom API version provision
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Util
 */
class AccessorHandler implements AccessorHandlerInterface
{

    /**
     * @var \ArrayObject
     */
    protected $accessorDefinitions;

    /**
     * @var \ArrayObject
     */
    protected $accessorSetter;

    public function __construct()
    {
        $this->accessorDefinitions = new \ArrayObject();
        $this->accessorSetter = new \ArrayObject();
    }

    /**
     * @param string $type
     * @param string $setter
     * @param string $modelName
     */
    public function addAccessor(string $type, string $setter, string $modelName)
    {
        $this->accessorDefinitions->offsetSet($type, $modelName);
        $this->accessorSetter->offsetSet($type, $setter);

        return $this;
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function getAccessor(string $type) : ?AccessorInterface
    {
        if($this->accessorDefinitions->offsetExists($type))
        {
            $model = $this->accessorDefinitions->offsetGet($type);
            return new $model($this);
        }

        throw new \BadMethodCallException(
            "BoxalinoApiResponse: the accessor does not have a model defined for $type . Please contact Boxalino"
        );
    }

    /**
     * @param string $type
     * @return bool
     */
    public function hasAccessor(string $type) : bool
    {
        return $this->accessorDefinitions->offsetExists($type);
    }

    /**
     * @param string $type
     * @return string
     */
    public function getAccessorSetter(string $type) : ?string
    {
        if($this->accessorSetter->offsetExists($type))
        {
            return $this->accessorSetter->offsetExists($type);
        }

        throw new \BadMethodCallException(
            "BoxalinoApiResponse: the accessor does not have a setter defined for $type . Please contact Boxalino."
        );
    }

}
