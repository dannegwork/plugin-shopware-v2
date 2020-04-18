<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Definition;

use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\ItemDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinitionInterface;
use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinition;

/**
 * Boxalino API Request definition interface for item context requests
 * (ex: recomendations on PDP, basket, blog articles, etc)
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
interface ItemRequestDefinitionInterface extends RequestDefinitionInterface
{
    /**
     * @param ItemDefinition ...$itemDefinitions
     * @return RequestDefinition
     */
    public function addItems(ItemDefinition ...$itemDefinitions) : ItemRequestDefinition;

}
