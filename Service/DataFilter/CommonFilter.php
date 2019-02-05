<?php

namespace Repregid\ApiBundle\Service\DataFilter;

/**
 * Class Filter
 * @package Repregid\ApiBundle\Service\DataFilter
 */
class CommonFilter
{
    const PAGE_DEFAULT      = 1;
    const PAGE_SIZE_DEFAULT = 30;
    const QUERY_DEFAULT     = '';

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