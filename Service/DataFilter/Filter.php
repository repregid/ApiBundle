<?php

namespace Repregid\ApiBundle\Service\DataFilter;

/**
 * Class Filter
 * @package Repregid\ApiBundle\Service\DataFilter
 */
class Filter
{
    const PAGE_DEFAULT      = 1;
    const PAGE_SIZE_DEFAULT = 30;
    const QUERY_DEFAULT     = '';

    /**
     * @var mixed
     */
    protected $filter;

    /**
     * @var FilterOrder[]
     */
    protected $sort;

    /**
     * @var int
     */
    protected $page = self::PAGE_DEFAULT;

    /**
     * @var int
     */
    protected $pageSize = self::PAGE_SIZE_DEFAULT;

    /**
     * @var string
     */
    protected $query = self::QUERY_DEFAULT;

    /**
     * Filter constructor.
     */
    public function __construct()
    {
        $this->sort     = self::getDefaultSort();
        $this->query    = self::QUERY_DEFAULT;
    }

    /**
     * @return array
     */
    public static function getDefaultSort()
    {
        return [new FilterOrder('id',  FilterOrder::ORDER_DESC)];
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param mixed $filter
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
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;

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
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }
}