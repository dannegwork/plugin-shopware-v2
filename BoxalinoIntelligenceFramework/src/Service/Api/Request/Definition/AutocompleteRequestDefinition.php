<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Definition;

use Boxalino\IntelligenceFramework\Service\Api\Request\RequestDefinition;

/**
 * Boxalino API Request definition object for autocomplete requests
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class AutocompleteRequestDefinition extends RequestDefinition
    implements AutocompleteContextRequestDefinitionInterface
{
    /**
     * @var int
     */
    protected $acQueriesHitCount = 0;

    /**
     * @var bool
     */
    protected $acHighlight = true;

    /**
     * @var string
     */
    protected $acHighlightPre = "<em>";

    /**
     * @var string
     */
    protected $acHighlightPost = "</em>";

    /**
     * @param int $hitCount
     * @return $this
     */
    public function setAcQueriesHitCount(int $hitCount) : AutocompleteRequestDefinition
    {
        $this->acQueriesHitCount = $hitCount;
        return $this;
    }

    /**
     * @param bool $highlight
     * @return $this
     */
    public function setAcHighlight(bool $highlight) : AutocompleteRequestDefinition
    {
        $this->acHighlight = $highlight;
        return $this;
    }

    /**
     * @param string $pre
     * @return $this
     */
    public function setAcHighlightPre(string $pre) : AutocompleteRequestDefinition
    {
        $this->acHighlightPre = $pre;
        return $this;
    }

    /**
     * @param string $post
     * @return $this
     */
    public function setAcHighlightPost(string $post) : AutocompleteRequestDefinition
    {
        $this->acHighlightPost = $post;
        return $this;
    }

}
