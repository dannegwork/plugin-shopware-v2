<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Response;

use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;
use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandler;
use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandlerInterface;

/**
 * Class ResponseHydratorTrait
 * Hydrates the response
 */
trait ResponseHydratorTrait
{

    /**
     * Transform an element to an object
     * (ex: a response element to the desired type)
     *
     * @param \StdClass $data
     * @param AccessorInterface $object
     * @return mixed
     */
    public function toObject(\StdClass $data, AccessorInterface $object) : AccessorInterface
    {
        $dataAsObject = new \ReflectionObject($data);
        $properties = $dataAsObject->getProperties();
        $class = get_class($object);
        $methods = get_class_methods($class);

        foreach($properties as $property)
        {
            $propertyName = $property->getName();
            $value = $data->$propertyName;
            $setter = "set" . strtoupper(substr($propertyName, 0, 1)) . substr($propertyName, 1);
            /**
             * accessor are informative Boxalino system variables which have no value to the integration system
             */
            if($value === ['accessor'] || $value === "accessor")
            {
                continue;
            }

            if(in_array($setter, $methods))
            {
                $object->$setter($value);
                continue;
            }

            if($this->getAccessorHandler()->hasAccessor($propertyName))
            {
                $handler = $this->getAccessorHandler()->getAccessor($propertyName);
                $valueObject = $this->toObject($value, $handler);
                $objectProperty = $this->getAccessorHandler()->getAccessorSetter($propertyName);

                $object->set($objectProperty, $valueObject);
                continue;
            }

            $object->set($propertyName, $value);
        }

        return $object;
    }

    /**
     * @return AccessorHandlerInterface
     */
    abstract function getAccessorHandler() : AccessorHandlerInterface;

    public function log($content)
    {
        if(property_exists($this, "logger"))
        {
            $this->logger->info($content);
        }
    }

}
