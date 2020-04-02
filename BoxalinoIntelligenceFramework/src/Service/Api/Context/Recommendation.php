<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Context;

use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\FacetDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\FilterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\HeaderParameterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\UserParameterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\ItemDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\SortingDefinition;
use Boxalino\IntelligenceFramework\Service\Api\RequestFactory;
use GuzzleHttp\Client;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class Recommendation extends RequestFactory
{

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @param ItemDefinition ...$itemDefinitions
     * @return $this
     */
    public function addItems(ItemDefinition ...$itemDefinitions) : self
    {
        foreach ($itemDefinitions as $item) {
            $this->items[] = $item->toArray();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getItems() : array
    {
        return $this->items;
    }

}
