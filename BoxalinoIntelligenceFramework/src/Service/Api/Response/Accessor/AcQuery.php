<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

/**
 * Class AcQuery
 * Model of the bx-acQuery element of the response
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Response\Accessor
 */
class AcQuery extends Accessor
    implements AccessorInterface
{

    /**
     * @var string
     */
    protected $suggestion;

    /**
     * @var null | string
     */
    protected $highlighted = null;

    /**
     * @var int
     */
    protected $totalHitCount = 0;

    /**
     * @return string
     */
    public function getSuggestion() : string
    {
        return $this->suggestion;
    }

    /**
     * @return string|null
     */
    public function getHighlighted() : ?string
    {
        return $this->highlighted;
    }

    /**
     * @param string $suggestion
     * @return $this
     */
    public function setSuggestion(string $suggestion)
    {
        $this->suggestion = $suggestion;
        return $this;
    }

    /**
     * @param null|string $highlighted
     * @return $this
     */
    public function setHighlighted(?string $highlighted)
    {
        $this->highlighted = $highlighted;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalHitCount()
    {
        return $this->totalHitCount;
    }

    /**
     * @param int $totalHitCount
     * @return $this
     */
    public function setTotalHitCount(int $totalHitCount)
    {
        $this->totalHitCount = $totalHitCount;
        return $this;
    }

}
