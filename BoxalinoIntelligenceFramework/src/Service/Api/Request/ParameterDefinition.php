<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

use Psr\Log\LoggerInterface;

/**
 * Class ParameterDefinition
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
class ParameterDefinition implements ParameterInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $boxalinoLogger)
    {
        $this->logger = $boxalinoLogger;
    }

    /**
     * The object is used as a base for the API request parameters
     * It should not fail in case an inexistent service is being requested
     *
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $callingClass = get_called_class();
        $this->logger->alert("BoxalinoApi: Calling $callingClass object method '$name' "
            . implode(', ', $arguments));

        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        $vars = get_object_vars($this);
        unset($vars["logger"]);
        return $vars;
    }

}
