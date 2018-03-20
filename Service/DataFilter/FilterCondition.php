<?php

namespace Repregid\ApiBundle\Service\DataFilter;

/**
 * Class FilterCondition
 * @package Repregid\ApiBundle\Service\DataFilter
 */
class FilterCondition
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var string
     */
    protected $value;

    /**
     * FilterCondition constructor.
     * @param string $field
     * @param string $operator
     * @param string $value
     */
    public function __construct(
        string $field = '',
        string $operator = '',
        string $value = ''
    ) {
        $this->field    = $field;
        $this->operator = $operator;
        $this->value    = $value;
    }

    /**
     * @param $field
     * @return $this
     */
    public function setField($field): FilterCondition
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
     * @param $operator
     * @return $this
     */
    public function setOperator($operator): FilterCondition
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value): FilterCondition
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue(): string
    {
        return $this->value;
    }
}