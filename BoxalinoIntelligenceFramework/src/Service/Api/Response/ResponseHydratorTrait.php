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

        /**
        $class = get_class($object);
        $methods = get_class_methods($class);

        foreach ($methods as $method)
        {
            preg_match(' /^(set)(.*?)$/i', $method, $results);
            $pre = $results[1]  ?? '';
            $property = $results[2]  ?? '';
            $property = strtolower(substr($property, 0, 1)) . substr($property, 1);
            if ($pre == 'set') {
                if(!empty($data->$property))
                {
                    if($this->getAccessorHandler()->hasAccessor($property))
                    {
                        $data = $this->toObject($data->$property, $this->getAccessorHandler()->getAccessor($property));
                        $setter = $this->getAccessorHandler()->getAccessorSetter($property);

                        $object->set($setter, $data);
                        continue;
                    }

                    $object->$method($data->$property);
                }
            }
        }
         */

        $dataAsObject = new \ReflectionObject($data);
        $properties = $dataAsObject->getProperties();
        $class = get_class($object);
        $methods = get_class_methods($class);

        foreach($properties as $property)
        {
            $propertyName = $property->getName();
            $value = $data->$propertyName;
            $setter = "set" . strtoupper(substr($propertyName, 0, 1)) . substr($propertyName, 1);
            if($value=="accessor")
            {
                continue;
            }
            if(in_array($setter, $methods))
            {
                #$this->logger->info("method setter $setter");
                #$this->logger->info(serialize($value));
                $object->$setter($value);
                continue;
            }

            /**
             * accessor are informative Boxalino system variables
             */
            #$this->logger->info(serialize($value));
            if(is_array($value) && isset($value[0]) && $value[0] == 'accessor')
            {
                continue;
            }

            if($this->getAccessorHandler()->hasAccessor($propertyName))
            {
                #$this->logger->info("accessor setter");
                $valueObject = $this->toObject($value, $this->getAccessorHandler()->getAccessor($propertyName));
                $objectProperty = $this->getAccessorHandler()->getAccessorSetter($propertyName);

                $object->set($objectProperty, $valueObject);
                continue;
            }

            #$this->logger->info("general set");
            $object->set($propertyName, $value);
        }

        return $object;
    }

    /**
     * @return AccessorHandlerInterface
     */
    abstract function getAccessorHandler() : AccessorHandlerInterface;

}
