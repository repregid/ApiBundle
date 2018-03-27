<?php

namespace Repregid\ApiBundle\Service\DataFilter\Form\DataTransformer;

use Repregid\ApiBundle\Service\DataFilter\FilterOrder;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class SortTransformer
 * @package Repregid\ApiBundle\Service\DataFilter\Form\DataTransformer
 */
class SortTransformer implements DataTransformerInterface
{
    public $filterSorts = [];

    /**
     * @param mixed $value
     * @return mixed|string
     */
    public function transform($value)
    {
        return '';
    }

    /**
     * @param mixed $value
     * @return array|mixed|void
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return;
        }

        $result = [];
        $sort  = explode(',', $value);

        foreach($sort as $item) {
            $criteria   = $item[0] === '-' ? FilterOrder::ORDER_DESC : FilterOrder::ORDER_ASC;
            $field      = $item[0] === '-' ? substr($item, 1) : $item;
            $result[]   = new FilterOrder($field, $criteria);
        }

        return $result;
    }
}