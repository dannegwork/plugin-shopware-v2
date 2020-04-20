<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Util;

use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;
use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorModelInterface;
use Psr\Container\ContainerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Config\Definition\Exception\Exception;
use Psr\Log\LoggerInterface;

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

    /**
     * @var \ArrayObject
     */
    protected $hitIdFieldName;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        $this->accessorDefinitions = new \ArrayObject();
        $this->accessorSetter = new \ArrayObject();
        $this->hitIdFieldName = new \ArrayObject();
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
            return $this->getModel($this->accessorDefinitions->offsetGet($type));
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
            return $this->accessorSetter->offsetGet($type);
        }

        throw new \BadMethodCallException(
            "BoxalinoApiResponse: the accessor does not have a setter defined for $type . Please contact Boxalino."
        );
    }

    /**
     * @param string $type
     * @param string $field
     * @return $this|mixed
     */
    public function addHitIdFieldName(string $type, string $field)
    {
        $this->hitIdFieldName->offsetSet($type, $field);
        return $this;
    }

    /**
     * @param string $type
     * @return string|null
     */
    public function getHitIdFieldName(string $type) : ?string
    {
        if($this->hitIdFieldName->offsetExists($type))
        {
            return $this->hitIdFieldName->offsetGet($type);
        }

        throw new \BadMethodCallException(
            "BoxalinoApiResponse: the accessor does not have a hit ID field name defined for $type . Please contact Boxalino."
        );
    }

    /**
     * @internal
     * @required
     */
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = $this->container;
        $this->container = $container;

        return $previous;
    }

    /**
     * @param string $type
     * @param mixed|null $context
     * @return mixed
     */
    public function getModel(string $type, $context = null)
    {
        try {
            if($this->container->has($type))
            {
                $service = $this->container->get($type);
                if($service instanceof AccessorModelInterface)
                {
                    $service->addAccessorContext($context);
                }

                return $service;
            }

            $model = new $type($this);
            if($model instanceof AccessorModelInterface)
            {
                $model->addAccessorContext($context);
            }

            return $model;
        } catch (\Exception $exception)
        {
            throw new \BadMethodCallException(
                "BoxalinoApiResponse: there was an issue accessing the service/model requested for $type. Original error: " . $exception->getMessage()
            );
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }



    /**
     * @TODO MIGRATE IN AN INTEGRATION LAYER AS THESE ARE SHOPWARE SPECIFIC
     */

    /**
     * @var SalesChannelContext
     */
    protected $salesChannelContext;

    /**
     * @return SalesChannelContext
     */
    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return AccessorHandler
     */
    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): AccessorHandler
    {
        $this->salesChannelContext = $salesChannelContext;
        return $this;
    }

}
