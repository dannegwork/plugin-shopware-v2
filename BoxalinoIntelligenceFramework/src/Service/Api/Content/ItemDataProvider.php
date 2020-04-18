<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Content;

use Boxalino\IntelligenceFramework\Service\Api\Response\HitDefinition;

/**
 * Class ItemDataProvider
 *
 * Item refers to any data model/logic that is desired to be rendered/displayed
 * The integrator can decide to either use all data as provided by the Narrative API,
 * or to design custom data layers to represent the fetched content
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Content
 */
class ItemDataProvider
{

    public function getItem(array $data) : HitDefinition
    {

    }

    public function getItemByResource(string $resourceClass, string $id) : HitDefinition
    {

    }

}
