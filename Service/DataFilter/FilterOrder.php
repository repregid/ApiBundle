<?php

namespace Repregid\ApiBundle\Service\DataFilter;


use Doctrine\Common\Collections\Criteria;

/**
 * Class FilterOrder
 * @package Repregid\ApiBundle\Service\DataFilter
 */
class FilterOrder
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $order;

    /**
     * FilterOrder constructor.
     * @param string $field
     * @param string $order
     */
    public function __construct(string $field, string $order = Criteria::ASC)
    {
        $this->field = $field;
        $this->order = $order;
    }

    /**
     * @param string $field
     * @return FilterOrder
     */
    public function setField(string $field): FilterOrder
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $order
     * @return FilterOrder
     */
    public function setOrder(string $order): FilterOrder
    {
        $this->operator = $order;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }
}