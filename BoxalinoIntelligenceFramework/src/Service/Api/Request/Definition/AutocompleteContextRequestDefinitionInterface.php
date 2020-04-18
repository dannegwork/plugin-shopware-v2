<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Definition;

use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinitionInterface;

/**
 * Boxalino API Request definition interface
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
interface AutocompleteContextRequestDefinitionInterface extends RequestDefinitionInterface
{
    /**
     * @param bool $highlight
     * @return mixed
     */
    public function setAcHighlight(bool $highlight) : AutocompleteRequestDefinition;

    /**
     * @param string $pre
     * @return mixed
     */
    public function setAcHighlightPre(string $pre) : AutocompleteRequestDefinition;

    /**
     * @param string $post
     * @return mixed
     */
    public function setAcHighlightPost(string $post) : AutocompleteRequestDefinition;

    /**
     * @param int $hitCount
     * @return mixed
     */
    public function setAcQueriesHitCount(int $hitCount) : AutocompleteRequestDefinition;

}
