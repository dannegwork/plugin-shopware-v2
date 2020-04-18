<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

interface ContextInterface
{
    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return RequestDefinitionInterface
     */
    public function get(Request $request, SalesChannelContext $salesChannelContext) : RequestDefinitionInterface;

    /**
     * @param string $widget
     * @return mixed
     */
    public function setWidget(string $widget);

    /**
     * @param RequestDefinitionInterface $requestDefinition
     * @return mixed
     */
    public function setRequestDefinition(RequestDefinitionInterface $requestDefinition);

    /**
     * @return RequestDefinitionInterface
     */
    public function getApiRequest() : RequestDefinitionInterface;
}
