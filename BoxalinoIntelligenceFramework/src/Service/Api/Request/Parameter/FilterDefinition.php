<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Parameter;

use Boxalino\IntelligenceFramework\Service\Api\Request\ParameterDefinition;

/**
 * Class FilterDefinition
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
class FilterDefinition extends ParameterDefinition
{

    /**
     * @param string $field
     * @param float $from
     * @param float $to
     * @param bool $fromInclusive
     * @param bool $toInclusive
     * @return FilterDefinition
     */
    public function addRange(string $field, float $from, float $to, bool $fromInclusive = true, bool $toInclusive = true) : self
    {
        $this->field = $field;
        $this->from = $from;
        $this->to = $to;
        $this->fromInclusive = $fromInclusive;
        $this->toInclusive = $toInclusive;

        return $this;
    }

    /**
     * @param string $field
     * @param array $values
     * @param bool $negative
     * @return FilterDefinition
     */
    public function add(string $field, array $values = [], bool $negative = false) : self
    {
        $this->field = $field;
        $this->values = $values;
        $this->negative = $negative;

        return $this;
    }

}
