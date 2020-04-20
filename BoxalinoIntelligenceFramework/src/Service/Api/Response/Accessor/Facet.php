<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandlerInterface;

class Facet extends Accessor
    implements AccessorInterface
{

    /**
     * @var string
     */
    protected $field;

    /**
     * @var null | string
     */
    protected $label = null;

    /**
     * Facet values
     * @var \ArrayIterator
     */
    protected $values;

    /**
     * @var bool
     */
    protected $isAndSelectedValues = false;

    /**
     * @var bool
     */
    protected $isBoundsOnly = false;

    /**
     * Numerical facet
     *
     * @var bool
     */
    protected $isNumerical = false;

    /**
     * Range facet (ex: price)
     *
     * @var bool
     */
    protected $isRange = false;

    /**
     * @var int
     */
    protected $sortCode = 1;

    /**
     * The order of the facet values in the view
     * As selected from the Boxalino Intelligence Admin Merchandising >> Facets
     *
     * @var string
     * alphabetical | counter | custom | 2 (store system order)
     */
    protected $valueorderEnums;

    /**
     * @var bool
     */
    protected $finderFacet = false;

    /**
     * Front-End visualisation (display/template) of the facet
     * as selected from the Boxalino Intelligence Admin Merchandising >> Facets
     * @var string
     *
     * enumeration | multiselect | range | radio | slider | pushbutton | switch | dropdown
     * | multiselect-dropdown | search | stars | colorpicker | datepicker | sizepicker | checkbox | genderpicker
     * | thumbs | textarea | textfield
     */
    protected $visualisation;

    /**
     * @var string (hidden |  expanded  | collapsed)
     */
    protected $display;

    /**
     * @var string ( top | inlist | only )
     */
    protected $displaySelectedValues;

    /**
     * Display number of products matching each facet value or not
     * @var bool
     */
    protected $showCounter;

    /**
     * If set, only the <enumDisplaySize> nr of facet value will be displayed, the other would appear under a link 'see other values'
     *
     * @var null | int
     */
    protected $enumDisplaySize = null;

    /**
     * Same as <enumDisplaySize>
     * Except it sets the maximum facet values to be returned
     *
     * @var null | int
     */
    protected $enumDisplayMaxSize = null;

    /**
     * If set, it will only return the facet if the total product coverage of the facet values reaches the limit
     *
     * @var null | float
     */
    protected $minDisplayCoverage = null;

    /**
     * @var bool
     */
    protected $sortAscending = false;


    public function __construct(AccessorHandlerInterface $accessorHandler)
    {
        $this->values = new \ArrayIterator();
        parent::__construct($accessorHandler);
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     * @return Facet
     */
    public function setField(string $field): Facet
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param array $label
     * @return Facet
     */
    public function setLabel(array $label): Facet
    {
        $this->label = $label[0] ?? null;
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getValues(): \ArrayIterator
    {
        return $this->values;
    }

    /**
     * @param array $values
     * @return Facet
     */
    public function setValues(array $values): Facet
    {
        foreach($this->values as $value)
        {
            $new = $this->toObject($value, new FacetValue());
            $this->values->append($new);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isAndSelectedValues(): bool
    {
        return $this->isAndSelectedValues;
    }

    /**
     * @param bool $isAndSelectedValues
     * @return Facet
     */
    public function setIsAndSelectedValues(bool $isAndSelectedValues): Facet
    {
        $this->isAndSelectedValues = $isAndSelectedValues;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBoundsOnly(): bool
    {
        return $this->isBoundsOnly;
    }

    /**
     * @param bool $isBoundsOnly
     * @return Facet
     */
    public function setIsBoundsOnly(bool $isBoundsOnly): Facet
    {
        $this->isBoundsOnly = $isBoundsOnly;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNumerical(): bool
    {
        return $this->isNumerical;
    }

    /**
     * @param bool $isNumerical
     * @return Facet
     */
    public function setIsNumerical(bool $isNumerical): Facet
    {
        $this->isNumerical = $isNumerical;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRange(): bool
    {
        return $this->isRange;
    }

    /**
     * @param bool $isRange
     * @return Facet
     */
    public function setIsRange(bool $isRange): Facet
    {
        $this->isRange = $isRange;
        return $this;
    }

    /**
     * @return int
     */
    public function getSortCode(): int
    {
        return $this->sortCode;
    }

    /**
     * @param int $sortCode
     * @return Facet
     */
    public function setSortCode(int $sortCode): Facet
    {
        $this->sortCode = $sortCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getValueorderEnums(): string
    {
        return $this->valueorderEnums;
    }

    /**
     * @param string $valueorderEnums
     * @return Facet
     */
    public function setValueorderEnums(string $valueorderEnums): Facet
    {
        $this->valueorderEnums = $valueorderEnums;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFinderFacet(): bool
    {
        return $this->finderFacet;
    }

    /**
     * @param bool $finderFacet
     * @return Facet
     */
    public function setFinderFacet(bool $finderFacet): Facet
    {
        $this->finderFacet = $finderFacet;
        return $this;
    }

    /**
     * @return string
     */
    public function getVisualisation(): string
    {
        return $this->visualisation;
    }

    /**
     * @param string $visualisation
     * @return Facet
     */
    public function setVisualisation(string $visualisation): Facet
    {
        $this->visualisation = $visualisation;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplay(): string
    {
        return $this->display;
    }

    /**
     * @param string $display
     * @return Facet
     */
    public function setDisplay(string $display): Facet
    {
        $this->display = $display;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplaySelectedValues(): string
    {
        return $this->displaySelectedValues;
    }

    /**
     * @param string $displaySelectedValues
     * @return Facet
     */
    public function setDisplaySelectedValues(string $displaySelectedValues): Facet
    {
        $this->displaySelectedValues = $displaySelectedValues;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowCounter(): bool
    {
        return $this->showCounter;
    }

    /**
     * @param bool $showCounter
     * @return Facet
     */
    public function setShowCounter(bool $showCounter): Facet
    {
        $this->showCounter = $showCounter;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEnumDisplaySize(): ?int
    {
        return $this->enumDisplaySize;
    }

    /**
     * @param int|null $enumDisplaySize
     * @return Facet
     */
    public function setEnumDisplaySize(?int $enumDisplaySize): Facet
    {
        $this->enumDisplaySize = $enumDisplaySize;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEnumDisplayMaxSize(): ?int
    {
        return $this->enumDisplayMaxSize;
    }

    /**
     * @param int|null $enumDisplayMaxSize
     * @return Facet
     */
    public function setEnumDisplayMaxSize(?int $enumDisplayMaxSize): Facet
    {
        $this->enumDisplayMaxSize = $enumDisplayMaxSize;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getMinDisplayCoverage(): ?float
    {
        return $this->minDisplayCoverage;
    }

    /**
     * @param float|null $minDisplayCoverage
     * @return Facet
     */
    public function setMinDisplayCoverage(?float $minDisplayCoverage): Facet
    {
        $this->minDisplayCoverage = $minDisplayCoverage;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSortAscending(): bool
    {
        return $this->sortAscending;
    }

    /**
     * @param bool $sortAscending
     * @return Facet
     */
    public function setSortAscending(bool $sortAscending): Facet
    {
        $this->sortAscending = $sortAscending;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSelected() : bool
    {
        //check if there are selected values
        array_filter($this->values,
            function (FacetValue $facetValue) {
                return $facetValue->isSelected() === true;
            }
        );

        return false;
    }

}
