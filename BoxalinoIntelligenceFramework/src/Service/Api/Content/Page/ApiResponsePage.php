<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Content\Page;

use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Storefront\Framework\Page\StorefrontSearchResult;
use Shopware\Storefront\Page\Page;

/**
 * Class AutocompletePageLoader
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Content\Page
 */
class ApiResponsePage extends Page
{
    /**
     * @var \ArrayIterator
     */
    protected $blocks;

    /**
     * @var string
     */
    protected $requestId;

    /**
     * @var string
     */
    protected $groupBy;

    /**
     * @var bool
     */
    protected $fallback = false;

    /**
     * @var string|null
     */
    protected $sorting;

    /**
     * @return array
     */
    public function getSortings(): array
    {
        return $this->sortings;
    }

    /**
     * @param array $sortings
     */
    public function setSortings(array $sortings): void
    {
        $this->sortings = $sortings;
    }

    /**
     * @return \ArrayIterator
     */
    public function getBlocks() : \ArrayIterator
    {
        return $this->blocks;
    }

    /**
     * @return string
     */
    public function getRequestId() : string
    {
        return $this->requestId;
    }

    /**
     * @return string
     */
    public function getGroupBy() : string
    {
        return $this->groupBy;
    }

    /**
     * @param \ArrayIterator $blocks
     * @return $this
     */
    public function setBlocks(\ArrayIterator $blocks) : self
    {
        $this->blocks = $blocks;
        return $this;
    }

    /**
     * @param string $groupBy
     * @return $this
     */
    public function setGroupBy(string $groupBy) : self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * @param string $requestId
     * @return $this
     */
    public function setRequestId(string $requestId) : self
    {
        $this->requestId = $requestId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFallback(): bool
    {
        return $this->fallback;
    }

    /**
     * @param bool $fallback
     * @return ApiResponsePage
     */
    public function setFallback(bool $fallback): ApiResponsePage
    {
        $this->fallback = $fallback;
        return $this;
    }

}