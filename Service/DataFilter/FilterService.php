<?php

namespace Repregid\ApiBundle\Service\DataFilter;


use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\CheckboxFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\CollectionAdapterFilterType;
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

    const OPERATORS_COMPLEX      = [self::OPERATOR_IS_NULL, self::OPERATOR_IS_NOT_NULL];
    const OPERATORS_ONE_SIDE     = [self::OPERATOR_IS_NULL, self::OPERATOR_IS_NOT_NULL];
    const OPERATORS_TWO_SIDES    = ['<>', '<=', '>=', '>', '<', self::OPERATOR_LIKE, '='];

    /**
     * @var array
     */
    protected $values     = [];

    /**
     * @var array
     */
    protected $operators  = [];

    /**
     * @var array
     */
    protected $sorts      = [];

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return array
     */
    public function getOperators(): array
    {
        return $this->operators;
    }

    /**
     * @return array
     */
    public function getSorts(): array
    {
        return $this->sorts;
    }

    /**
     * FilterService constructor.
     *
     * @param string $filter
     * @param string $sort
     */
    public function __construct(string $filter, string $sort)
    {
        $this->parseFilter($filter);
        $this->parseSorts($sort);
    }

    /**
     * @param $string
     */
    protected function parseFilter($string)
    {
        $cache = [];

        $decodedFilter  = urldecode($string);
        $filterArray    = explode('&', $decodedFilter);

        foreach ($filterArray as $condition) {
            $selectedOperator = null;
            $parsedCondition = $this->parseExpr($condition);
            if ($parsedCondition !== null) {
                $cache[] = $parsedCondition;
            }
        }

        foreach($cache as $condition) {
            $fields = explode('.', $condition[0]);
            $fields = array_reverse($fields);

            $value = $condition[2];
            foreach ($fields as $field) {
                $value = [$field => $value];
            }

            $this->values = array_merge_recursive($this->values, $value);

            $this->operators[$condition[0]] = $condition[1];
        }
    }

    /**
     * @param $string
     */
    protected function parseSorts($string)
    {
        $sort = explode(',', $string);

        foreach($sort as $item) {
            $criteria   = $item[0] === '-' ? 'DESC' : 'ASC';
            $path       = $item[0] === '-' ? substr($item, 1) : $item;
            $field      = explode('.', $path);

            $this->sorts[$path]   = new FilterOrder(end($field), $criteria);
        }
    }

    /**
     * @param $expr
     * @return array|null
     */
    protected function parseExpr($expr): ?array
    {
        $available = array_merge(self::OPERATORS_TWO_SIDES, self::OPERATORS_ONE_SIDE);

        foreach ($available as $operator) {

            $isOneSide  = in_array($operator, static::OPERATORS_ONE_SIDE);
            $isComplex  = in_array($operator, static::OPERATORS_COMPLEX);
            $pattern    = preg_quote($operator);
            $pattern    = $isComplex ? "/\\s$pattern\\b/" : "/$pattern/";

            $dividedExpr = preg_split($pattern, $expr);

            if (count($dividedExpr) === 2) {
                $value = $isOneSide ? true : trim($dividedExpr[1]);
                return [$dividedExpr[0], $operator, $value];
            }
        }

        return null;
    }

    /**
     * @param FormInterface $form
     * @param string $name
     * @param string $alias
     */
    public function prepareFormField(FormInterface $form, string $name = '', string $alias = '')
    {
        /**
         * @var $child FormInterface
         */
        foreach ($form->all() as $child) {

            $config     = $child->getConfig();
            $childName  = $child->getName();
            $childType  = $config->getType()->getInnerType();

            $field = $name.(!$name ? '' : '.').$childName;

            if ($config->hasOption('shared_keys')) {

                $sharedKeys = $config->getOption('shared_keys');

                $options = array_replace(
                    $config->getOptions(),
                    ['add_shared' => self::getSharedFilter($sharedKeys[0], $sharedKeys[1])]
                );

                $form->add($childName, get_class($childType), $options);

                $child = $form->get($childName);

                $this->prepareFormField(
                    $childType instanceof CollectionAdapterFilterType ? $child->get(0) : $child,
                    $field,
                    $alias.$sharedKeys[1]
                );

                if($child->count() === 0) {
                    $form->remove($childName);
                    continue;
                }
            } else {
                if(array_key_exists($field, $this->sorts)) {

                    $this->sorts[$field]->setAlias($alias);

                } elseif (array_key_exists($field, $this->operators)) {

                    $isOneSide  = in_array($this->operators[$field], self::OPERATORS_ONE_SIDE);

                    $type       = $isOneSide ? CheckboxFilterType::class : get_class($childType);
                    $options    = $isOneSide ? [] : $config->getOptions();

                    $options    = array_replace($options, ['apply_filter' => self::getApplyFilter($this->operators[$field])]);

                    $form->add($childName, $type, $options);

                } else {
                    $form->remove($childName);
                    continue;
                }
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

            $value  = $values['value'];
            $expr   = $field.' '.$comparison;
            $param  = [];

            if(in_array($comparison, self::OPERATORS_TWO_SIDES)) {

                if($comparison === self::OPERATOR_LIKE) {
                    $comparison = 'LIKE';
                    $value = "%$value%";
                }

                $expr   = new Comparison($field, $comparison, ':'.$paramName);
                $param  = [$paramName => $value];
            }

            return $filterQuery->createCondition($expr, $param);
        };
    }

    /**
     * @param string $newName
     * @param string $newAlias
     * @return \Closure
     */
    public static function getSharedFilter($newName, $newAlias): \Closure
    {
        return function (FilterBuilderExecuterInterface $qbe) use ($newName, $newAlias) {
            $closure = function (QueryBuilder $filterBuilder, $alias, $joinAlias, Expr $expr) use ($newName, $newAlias) {
                $filterBuilder->leftJoin($alias.'.'.$newName, $joinAlias);
            };

            $qbe->addOnce($qbe->getAlias().'.'.$newName, $qbe->getAlias().$newAlias, $closure);
        };
    }
}