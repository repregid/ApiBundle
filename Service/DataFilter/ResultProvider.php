<?php

namespace Repregid\ApiBundle\Service\DataFilter;

/**
 * Class ResultProvider
 * @package Repregid\ApiBundle\Repository
 */
class ResultProvider
{
    /**
     * @var array
     */
    protected $results;

    /**
     * @var int
     */
    protected $totalCount;

    /**
     * @var int
     */
    protected $pageSize;

    /**
     * ResultProvider constructor.
     * @param array $results
     * @param int $totalCount
     * @param int $pageSize
     */
    public function __construct(array $results, int $totalCount, int $pageSize)
    {
        $this->results      = $results;
        $this->totalCount   = $totalCount;
        $this->pageSize     = $pageSize > 0 ? $pageSize : ($totalCount ?: 1);
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param array $results
     * @return ResultProvider
     */
    public function setResults(array $results): ResultProvider
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @return float
     */
    public function getTotalPages(): float
    {
        return ceil($this->totalCount / $this->pageSize);
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return mixed|null
     */
    public function getSingleResult()
    {
        return $this->results[0] ?? null;
    }
}