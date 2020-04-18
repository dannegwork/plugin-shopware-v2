<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandlerInterface;

/**
 * @package Boxalino\IntelligenceFramework\Service\Api\Response\Accessor
 */
class FacetList extends Accessor
    implements AccessorInterface
{
    /**
     * @var \ArrayIterator
     */
    protected $facets;

    /**
     * @var \ArrayIterator
     */
    protected $selectedFacets;

    public function __construct(AccessorHandlerInterface $accessorHandler)
    {
        $this->facets = new \ArrayIterator();
        $this->selectedFacets = new \ArrayIterator();
        parent::__construct($accessorHandler);
    }

    /**
     * @return \ArrayIterator
     */
    public function getFacets() :  \ArrayIterator
    {
        return $this->facets;
    }

    /**
     * @return \ArrayIterator
     */
    public function getSelectedFacets() : \ArrayIterator
    {
        return $this->selectedFacets;
    }

    /**
     * @param array $facets
     * @return $this
     */
    public function setFacets(array $facets) : self
    {
        foreach($facets as $facet)
        {
            $new = $this->toObject($facet, new Facet());
            if($new->isSelected())
            {
                $this->addSelectedFacet($new);
            }

            $this->facets->append($new);
        }

        return $this;
    }

    /**
     * @param AccessorInterface $facet
     * @return $this
     */
    public function addSelectedFacet(AccessorInterface $facet) : self
    {
        $this->selectedFacets->append($facet);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSelectedFacets() : bool
    {
        return (bool) $this->selectedFacets->count();
    }

    /**
     * @return bool
     */
    public function hasFacets() : bool
    {
        return (bool) $this->facets->count();
    }
}
