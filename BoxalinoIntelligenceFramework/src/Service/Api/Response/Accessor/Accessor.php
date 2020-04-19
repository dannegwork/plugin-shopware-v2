<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

use Boxalino\IntelligenceFramework\Service\Api\Response\ResponseHydratorTrait;
use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandler;
use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandlerInterface;

/**
 * @package Boxalino\IntelligenceFramework\Service\Api\Response\Accessor
 */
class Accessor implements AccessorInterface
{
    use ResponseHydratorTrait;

    /**
     * @var \ArrayObject
     */
    protected $accessors;

    /**
     * @var \ArrayObject
     */
    protected $accessorFields;

    /**
     * @var AccessorHandlerInterface
     */
    protected $accessorHandler;

    public function __construct(AccessorHandlerInterface $accessorHandler)
    {
        $this->accessors = new \ArrayObject();
        $this->accessorFields = new \ArrayObject();
        $this->accessorHandler = $accessorHandler;
    }

    /**
     * Dynamically add properties to the object
     *
     * @param string $methodName
     * @param null $params
     * @return $this
     */
    public function __call(string $methodName, $params = null)
    {
        $methodPrefix = substr($methodName, 0, 3);
        $key = strtolower(substr($methodName, 3));
        if($methodPrefix == 'get')
        {
            return $this->$key;
        }

        throw new \BadMethodCallException(
            "BoxalinoApiResponse: the accessor does not have a property defined for $key . Please contact Boxalino."
        );
    }

    /**
     * Sets either accessor objects or accessor fields to the response object
     *
     * @param string $propertyName
     * @param $content
     * @return $this
     */
    public function set(string $propertyName, $content)
    {
        $this->$propertyName = $content;
        return $this;
    }

    /**
     * @return AccessorHandlerInterface
     */
    public function getAccessorHandler(): AccessorHandlerInterface
    {
        return $this->accessorHandler;
    }

}
