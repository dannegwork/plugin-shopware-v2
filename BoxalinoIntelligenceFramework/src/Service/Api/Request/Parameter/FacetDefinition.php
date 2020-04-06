<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Parameter;

use Boxalino\IntelligenceFramework\Service\Api\Request\ParameterDefinition;

/**
 * Class FacetDefinition
 * Set facets to the request (dependable on the context and custom configurations)
 * A facet is defined by: field, maxCount, minPopulation, sort, sortAscending, andSelectedValues
 *
 * for sorting to be used: 1 = by counter value (population / number of response hits) 2 = by alphabetical order
 *
 * Facets can be with selected values or without
 * They have different definitions
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
class FacetDefinition extends ParameterDefinition
{

    CONST BOXALINO_REQUEST_FACET_SORT_COUNT = "1";
    CONST BOXALINO_REQUEST_FACET_SORT_ALPHABET = "2";

    /**
     * @param string $field
     * @param array $values
     * @param bool $urlField
     * @param bool $urlValues
     * @return FacetDefinition
     */
    public function addWithValues(string $field, array $values, bool $urlField = false, bool $urlValues = false) : self
    {
        $this->field = $field;
        $this->urlField = $urlField;
        $this->values = $values;
        $this->urlValues = $urlValues;

        return $this;
    }

    /**
     * @param string $field
     * @param float $from
     * @param float $to
     * @param bool $boundsOnly
     * @return FacetDefinition
     */
    public function addRange(string $field, float $from, float $to) : self
    {
        $this->field = $field;
        $this->from = $from;
        $this->to = $to;
        $this->boundsOnly = true;
        $this->numerical = true;
        $this->range = true;
        $this->maxCount = -1;
        $this->minPopulation = 1;

        return $this;
    }

    /**
     * @param string $field
     * @param int $maxCount
     * @param int $minPopulation
     * @param string $sort
     * @param bool $sortAscending
     * @param bool $andSelectedValues
     * @return FacetDefinition
     */
    public function add(string $field, int $maxCount, int $minPopulation, string $sort = self::BOXALINO_REQUEST_FACET_SORT_COUNT, bool $sortAscending = false, bool $andSelectedValues = false) : self
    {
        $this->field = $field;
        $this->maxCount = $maxCount;
        $this->minPopulation = $minPopulation;
        $this->sort = $sort;
        if(!in_array($sort, [self::BOXALINO_REQUEST_FACET_SORT_ALPHABET, self::BOXALINO_REQUEST_FACET_SORT_COUNT]))
        {
            $this->sort = self::BOXALINO_REQUEST_FACET_SORT_COUNT;
        }
        $this->sortAscending = $sortAscending;
        $this->andSelectedValues = $andSelectedValues;

        return $this;
    }

}
