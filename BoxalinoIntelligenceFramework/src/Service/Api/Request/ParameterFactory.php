<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ParameterFactory
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
class ParameterFactory
{
    CONST BOXALINO_API_REQUEST_PARAMETER_SERVICE_PREFIX = "boxalino.api.request.parameter.";
    CONST BOXALINO_API_REQUEST_PARAMETER_TYPE_FACET = "facet";
    CONST BOXALINO_API_REQUEST_PARAMETER_TYPE_FILTER = "filter";
    CONST BOXALINO_API_REQUEST_PARAMETER_TYPE_ITEM = "item";
    CONST BOXALINO_API_REQUEST_PARAMETER_TYPE_SORT = "sort";
    CONST BOXALINO_API_REQUEST_PARAMETER_TYPE_USER = "user";
    CONST BOXALINO_API_REQUEST_PARAMETER_TYPE_HEADER = "header";
    CONST BOXALINO_API_REQUEST_PARAMETER_DEFINITION = "definition";

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ContainerInterface $container,
        LoggerInterface $boxalinoLogger
    ){
        $this->container = $container;
        $this->logger = $boxalinoLogger;
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function get(string $type) : ? ParameterInterface
    {
        $serviceId = self::BOXALINO_API_REQUEST_PARAMETER_SERVICE_PREFIX.strtolower($type);
        if($this->container->has($serviceId))
        {
            $service =  $this->container->get($serviceId);
            if($service instanceof ParameterInterface)
            {
                return $service;
            }
            $this->logger->warning("BoxalinoApi: the requested service does not follow the required interface: $serviceId");
            return $this->container->get($this->getDefaultParameterServerId());
        }

        $this->logger->error("BoxalinoApi: the requested service does not exist: $serviceId; The default parameter service will be used.");
        return $this->container->get($this->getDefaultParameterServerId());
    }

    /**
     * @return string
     */
    public function getDefaultParameterServerId() : string
    {
        return self::BOXALINO_API_REQUEST_PARAMETER_SERVICE_PREFIX . self::BOXALINO_API_REQUEST_PARAMETER_DEFINITION;
    }

}
