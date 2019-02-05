<?php

namespace Repregid\ApiBundle\Service\DataFilter;

/**
 * Class Filter
 * @package Repregid\ApiBundle\Service\DataFilter
 */
class Filter
{
    const FILTER_DEFAULT    = [];

    /**
     * @var array
     */
    protected $filter = self::FILTER_DEFAULT;

    /**
     * @var FilterOrder[]
     */
    protected $sort;

    /**
     * @var int
     */
    protected $index = 0;

    /**
     * Filter constructor.
     */
    public function __construct()
    {
        $this->sort = self::getDefaultSort();
    }

    /**
     * @return array
     */
    public static function getDefaultSort()
    {
        return ['id' => new FilterOrder('id',  FilterOrder::ORDER_DESC)];
    }

    /**
     * @return array
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * @param array $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return FilterOrder[]
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * @param array $sort
     * @return $this
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @param int $index
     * @return $this
     */
    public function setIndex(int $index): self
    {
        $this->index = $index;
        return $this;
    }
}