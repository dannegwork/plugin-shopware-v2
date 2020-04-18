<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

/**
 * Class Sort
 * Model of the BX-SORT response accessor
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Response\Accessor
 */
class Sort extends Accessor
    implements AccessorInterface
{

    /**
     * @var string
     */
    protected $field;

    /**
     * @var bool
     */
    protected $reverse;

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     * @return Sort
     */
    public function setField(string $field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getReverse()
    {
        return $this->reverse;
    }

    /**
     * When true, sort direction is DESC
     * When false, sort direction is ASC
     *
     * @param bool $reverse
     * @return Sort
     */
    public function setReverse(bool $reverse)
    {
        $this->reverse = $reverse;
        return $this;
    }

}
