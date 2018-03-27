<?php

namespace Repregid\ApiBundle\Service\DataFilter;


use Doctrine\ORM\Query\Expr\Comparison;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\CollectionAdapterFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\EmbeddedFilterTypeInterface;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class FilterService
 * @package Repregid\ApiBundle\Service
 */
class FilterService
{
    const OPERATOR_LIKE         = '=?';
    const OPERATOR_IS_NULL      = 'IS NULL';
    const OPERATOR_IS_NOT_NULL  = 'IS NOT NULL';

    const OPERATORS_COMPLEX     = [self::OPERATOR_IS_NULL, self::OPERATOR_IS_NOT_NULL];
    const OPERATORS_SIMPLE      = ['<=', '>=', '>', '<', self::OPERATOR_LIKE, '='];

    const AVAILABLE_OPERATORS   = self::OPERATORS_SIMPLE + self::OPERATORS_COMPLEX;

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @param FormInterface $form
     * @param array $operators
     */
    public static function prepareForm(FormInterface $form, array $operators): void
    {
        /**
         * @var $child FormInterface
         */
        foreach ($form->all() as $child) {

            $config     = $child->getConfig();
            $formType   = $config->getType()->getInnerType();
            $formName   = $child->getName();

            if(!isset($operators[$formName])) {
                continue;
            }

            if ($config->hasAttribute('add_shared')) {

                is_array($operators[$formName]) &&
                self::prepareForm(
                    $formType instanceof CollectionAdapterFilterType ? $child->get(0) : $child,
                    $operators[$formName]
                );

            } elseif ($formType instanceof EmbeddedFilterTypeInterface) {

                is_array($operators[$formName]) &&
                self::prepareForm($child, $operators[$formName]);

            } else {

                is_scalar($operators[$formName]) &&
                $child->getParent()->add(
                    $formName,
                    get_class($formType),
                    array_replace($config->getOptions(), [
                    'apply_filter' => self::getApplyFilter($operators[$formName])
                    ])
                );
            }
        }
    }

    /**
     * @param $comparison
     * @return \Closure
     */
    protected static function getApplyFilter($comparison): \Closure
    {
        return function(QueryInterface $filterQuery, $field, $values) use ($comparison) {
            if (empty($values['value'])) {
                return null;
            }

            $paramName = sprintf('p_%s', str_replace('.', '_', $field));

            return $filterQuery->createCondition(
                new Comparison($field, $comparison, ':'.$paramName),
                array($paramName => $values['value'])
            );
        };
    }

    /**
     * @return array
     */
    public function parseString($string, $operators = false): array
    {
        if(!$this->cache) {
            $decodedFilter  = urldecode($string);
            $filterArray    = explode('&', $decodedFilter);

            foreach ($filterArray as $condition) {
                $selectedOperator = null;
                $parsedCondition = self::parseExpr($condition);
                if ($parsedCondition !== null) {
                    $this->cache[] = $parsedCondition;
                }
            }
        }

        $values = [];

        foreach($this->cache as $condition) {
            $fields = explode('.', $condition[0]);
            $fields = array_reverse($fields);

            $value = $operators ? $condition[1] : $condition[2];
            foreach ($fields as $field) {
                $value = [$field => $value];
            }

            $values = array_merge_recursive($values, $value);
        }

        return $values;
    }

    /**
     * @param $expr
     * @return array|null
     */
    protected static function parseExpr($expr): ?array
    {
        foreach (self::AVAILABLE_OPERATORS as $operator) {
            $pattern = preg_quote($operator);
            if (!in_array($operator, static::OPERATORS_SIMPLE)) {
                $pattern = "\\s$pattern\\b";
            }
            $pattern = "/$pattern/";
            $dividedExpr = preg_split($pattern, $expr);
            if (count($dividedExpr) === 2) {
                $value = trim($dividedExpr[1]);

                if ($operator === '=?') {
                    $operator = 'LIKE';
                    $value = "%$value%";
                }

                return [$dividedExpr[0], $operator, $value];
            }
        }

        return null;
    }
}