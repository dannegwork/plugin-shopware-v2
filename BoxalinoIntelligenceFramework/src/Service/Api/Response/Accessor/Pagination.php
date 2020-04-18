<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Response\Accessor;

/**
 * Class Pagination
 * Model for the BX-PAGINATION response accessor
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Response\Accessor
 */
class Pagination extends Accessor
    implements AccessorInterface
{
    /**
     * @var int
     */
    protected $totalHitCount = 0;

    /**
     * @var int
     */
    protected $pageSize = 0;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var int
     */
    protected $currentPage = 1;

    /**
     * @var int
     */
    protected $lastPage = 1;

    /**
     * @return int
     */
    public function getTotalHitCount(): int
    {
        return $this->totalHitCount;
    }

    /**
     * @param int $totalHitCount
     * @return Pagination
     */
    public function setTotalHitCount(int $totalHitCount): Pagination
    {
        $this->totalHitCount = $totalHitCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return Pagination
     */
    public function setPageSize(int $pageSize): Pagination
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return Pagination
     */
    public function setOffset(int $offset): Pagination
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return float
     */
    public function getCurrentPage()
    {
        $this->currentPage = ceil($this->getOffset() / $this->getPageSize());
        if($this->currentPage)
        {
            return $this->currentPage;
        }

        return 1;
    }

    /**
     * @return float
     */
    public function getLastPage()
    {
        $this->lastPage = ceil($this->getTotalHitCount() / $this->getPageSize());
        if($this->lastPage)
        {
            return $this->lastPage;
        }

        return 1;
    }

}
