<?php

namespace Repregid\ApiBundle\Service\DataFilter;


use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Lexik\Bundle\FormFilterBundle\Filter\FilterOperands;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\CheckboxFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\CollectionAdapterFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeRangeFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\NumberFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\NumberRangeFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\CollectionFilterType;
use Symfony\Component\Form\FormInterface;

/**
 * Class FilterService
 * @package Repregid\ApiBundle\Service
 */
class FilterService
{
    const OPERATOR_LIKE         = '=?';
    const OPERATOR_IN           = 'IN';
    const OPERATOR_IS_NULL      = 'IS NULL';
    const OPERATOR_IS_NOT_NULL  = 'IS NOT NULL';
    const OPERATOR_BETWEEN      = 'BETWEEN';

    const OPERATORS_MULTIPLE    = [self::OPERATOR_IN, self::OPERATOR_BETWEEN];
    const OPERATORS_COMPLEX     = [self::OPERATOR_IS_NULL, self::OPERATOR_IS_NOT_NULL, self::OPERATOR_IN, self::OPERATOR_BETWEEN];
    const OPERATORS_ONE_SIDE    = [self::OPERATOR_IS_NULL, self::OPERATOR_IS_NOT_NULL];
    const OPERATORS_TWO_SIDES   = ['<>', '<=', '>=', '>', '<', self::OPERATOR_LIKE, '=', self::OPERATOR_IN, self::OPERATOR_BETWEEN];

    const DELIMITER             = ',';

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
    public function getNestedValues(): array
    {
        $result = [];

        foreach($this->values as $field => $value) {
            $fields = explode('.', $field);
            $fields = array_reverse($fields);

            foreach ($fields as $field) {
                $value = [$field => $value];
            }

            $result = array_merge_recursive($result, $value);
        }

        return $result;
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
     * @param string $extraFilter
     */
    public function __construct(string $filter, string $sort, string $extraFilter = '')
    {
        $this->parseFilter($filter, $extraFilter);
        $this->parseSorts($sort);
    }

    /**
     * @param $filter
     * @param $extraFilter
     */
    protected function parseFilter($filter, $extraFilter)
    {
        $cache = [];

        $decodedFilter  = urldecode($filter);
        $filterArray    = array_merge(
            explode('&', $decodedFilter),
            explode('&', $extraFilter)
        );

        foreach ($filterArray as $condition) {
            $selectedOperator = null;
            $parsedCondition = $this->parseExpr($condition);
            if ($parsedCondition !== null) {
                $cache[] = $parsedCondition;
            }
        }

        foreach($cache as $condition) {
            $this->values[$condition[0]]    = $condition[2];
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

            $isOneSide  = in_array($operator, self::OPERATORS_ONE_SIDE);
            $isComplex  = in_array($operator, self::OPERATORS_COMPLEX);
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
     * @param int $index
     * @param string $alias
     */
    public function prepareFormField(FormInterface $form, int $index, string $name = '', string $alias = '')
    {
        /**
         * @var $child FormInterface
         */
        foreach ($form->all() as $child) {

            $config     = $child->getConfig();
            $childName  = $child->getName();
            $childType  = $config->getType()->getInnerType();

            $field = $name.(!$name ? '' : '.').$childName;

            /**
             * Рекурсия на потомков
             */
            if ($config->hasOption('shared_keys')) {

                $sharedKeys = $config->getOption('shared_keys');

                $options = array_replace(
                    $config->getOptions(),
                    ['add_shared' => self::getSharedFilter($sharedKeys[0], $sharedKeys[1]. "_$index")]
                );

                $form->add($childName, get_class($childType), $options);

                $child = $form->get($childName);

                $this->prepareFormField(
                    $childType instanceof CollectionAdapterFilterType ? $child->get(0) : $child,
                    $index,
                    $field,
                    $alias.$sharedKeys[1]. "_$index"
                );

                if($child->count() === 0) {
                    $form->remove($childName);
                    continue;
                }
            /**
             * Самый младший ребенок
             */
            } else {

                if(
                    !array_key_exists($field, $this->values) &&
                    !array_key_exists($field, $this->sorts)
                ) {
                    $form->remove($childName);
                    continue;
                }

                if(array_key_exists($field, $this->sorts)) {
                    $this->sorts[$field]->setAlias($alias);
                }

                if (
                    array_key_exists($field, $this->operators) &&
                    array_key_exists($field, $this->values)
                ) {
                    $isOneSide  = in_array($this->operators[$field], self::OPERATORS_ONE_SIDE);
                    $isMultiple = in_array($this->operators[$field], self::OPERATORS_MULTIPLE);

                    /**
                     * 1.   По умолчанию в форме оставляем все как есть.
                     */
                    $type           = get_class($childType);
                    $options        = $config->getOptions();
                    $applyFilter    = true;

                    /**
                     * 2.   При одностороннем операторе выставляем тип Checkbox, а значение у него будет true
                     *      Таким образом у нас получится валидная форма для такого типа операторов.
                     */
                    if($isOneSide) {
                        $type       = CheckboxFilterType::class;
                        $options    = [];
                    } elseif($isMultiple) {
                        $mValue = explode(self::DELIMITER, $this->values[$field]);
                        $applyFilter = false;
                        switch ($this->operators[$field]) {
                            /**
                             * 2.1  Если IN, то преобразовываем поле в коллекцию таких полей.
                             */
                            case self::OPERATOR_IN: {
                                $type = CollectionFilterType::class;
                                $options = [
                                    'entry_type'    => get_class($childType),
                                    'entry_options' => $config->getOptions(),
                                    'allow_add'     => true
                                ];
                                $this->values[$field] = $mValue;
                                $applyFilter = true;
                                break;
                            }
                            /**
                             * 2.2  Если between, то преобразовываем поле в соответствующий тип Range.
                             *      В данном случае нам необходимо чтобы значение состояло минимум из двух элементов.
                             */
                            case self::OPERATOR_BETWEEN: {
                                if(count($mValue) < 2) {
                                    break;
                                }
                                switch (get_class($childType)) {
                                    case DateTimeFilterType::class: {
                                        $type = DateTimeRangeFilterType::class;
                                        $options = [
                                            'left_datetime_options'     => $config->getOptions(),
                                            'right_datetime_options'    => $config->getOptions()
                                        ];
                                        $this->values[$field] = [
                                            'left_datetime'             => $mValue[0],
                                            'right_datetime'            => $mValue[1]
                                        ];
                                        break;
                                    }
                                    case NumberFilterType::class: {
                                        $type = NumberRangeFilterType::class;
                                        $options = [
                                            'left_number_options'       => array_merge(
                                                $config->getOptions(),
                                                ['condition_operator' => FilterOperands::OPERATOR_GREATER_THAN_EQUAL]
                                            ),
                                            'right_number_options'      => array_merge(
                                                $config->getOptions(),
                                                ['condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL]
                                            )
                                        ];
                                        $this->values[$field] = [
                                            'left_number'               => $mValue[0],
                                            'right_number'              => $mValue[1]
                                        ];
                                        break;
                                    }
                                }
                                break;
                            }
                        }
                    }

                    if($applyFilter) {
                        $options = array_replace($options, ['apply_filter' => self::getApplyFilter($this->operators[$field])]);
                    }

                    $form->add($childName, $type, $options);
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

            $pavamValue  = $values['value'];
            $expr   = $field.' '.$comparison;
            $param  = [];

            if(in_array($comparison, self::OPERATORS_TWO_SIDES)) {
                $rvalue = ':'.$paramName;

                if($comparison === self::OPERATOR_LIKE) {
                    $comparison = 'LIKE';
                    $pavamValue = mb_strtolower($pavamValue, 'UTF-8');
                    $pavamValue = "%$pavamValue%";
                    $field = "LOWER($field)";
                }

                if ($comparison === self::OPERATOR_IN) {
                    $pavamValue = [$pavamValue, Connection::PARAM_STR_ARRAY];
                    $rvalue = '('.$rvalue.')';
                }

                $expr   = new Comparison($field, $comparison, $rvalue);
                $param  = [$paramName => $pavamValue];
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