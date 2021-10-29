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
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\DateFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\DateRangeFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\DateTimeRangeFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\NumberFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type\NumberRangeFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Repregid\ApiBundle\DQLFunction\JsonbArrayFunction;
use Repregid\ApiBundle\DQLFunction\JsonbExistAnyFunction;
use Repregid\ApiBundle\DQLFunction\JsonbExtractPathTextFunction;
use Repregid\ApiBundle\DQLFunction\ToJsonbFunction;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\CollectionFilterType;
use Scienta\DoctrineJsonFunctions\Query\AST\Functions\Postgresql\JsonGetPathText;
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
    const OPERATOR_JSON_ARRAY   = '?|';
    const OPERATOR_JSON_IN          = 'JIN';
    const OPERATOR_JSON_IS_NULL     = 'JISNULL';
    const OPERATOR_JSON_IS_NOT_NULL = 'JISNOTNULL';
    const OPERATOR_INSTANCE_OF      = 'IOF';
    const OPERATOR_INSTANCE_OF_IN   = 'IOFIN';

    const OPERATORS_MULTIPLE =  [self::OPERATOR_IN, self::OPERATOR_BETWEEN, self::OPERATOR_JSON_ARRAY, self::OPERATOR_JSON_IN, self::OPERATOR_INSTANCE_OF_IN];
    const OPERATORS_COMPLEX =   [self::OPERATOR_IS_NULL, self::OPERATOR_IS_NOT_NULL, self::OPERATOR_IN, self::OPERATOR_BETWEEN, self::OPERATOR_JSON_IN, self::OPERATOR_JSON_IS_NULL, self::OPERATOR_JSON_IS_NOT_NULL, self::OPERATOR_INSTANCE_OF, self::OPERATOR_INSTANCE_OF_IN];
    const OPERATORS_ONE_SIDE =  [self::OPERATOR_IS_NULL, self::OPERATOR_IS_NOT_NULL];
    const OPERATORS_TWO_SIDES = ['<>', '<=', '>=', '>', '<', self::OPERATOR_LIKE, '=', self::OPERATOR_IN, self::OPERATOR_BETWEEN, self::OPERATOR_JSON_ARRAY, self::OPERATOR_JSON_IN, self::OPERATOR_JSON_IS_NULL, self::OPERATOR_JSON_IS_NOT_NULL, self::OPERATOR_INSTANCE_OF, self::OPERATOR_INSTANCE_OF_IN];

    const DELIMITER = ',';

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var array
     */
    protected $operators = [];

    /**
     * @var array
     */
    protected $sorts = [];

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

        foreach ($this->values as $field => $value) {
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
     * @param array $filter
     * @param array $sort
     */
    public function __construct(array $filter, array $sort)
    {
        foreach ($filter as $field => $condition) {
            $this->values[$field] = $condition[1];
            $this->operators[$field] = $condition[0];
        }

        $this->sorts = $sort;
    }

    public static function parseFilters(string $filter, string $extraFilter): array
    {
        $cache = [];

        $decodedFilter = urldecode($filter);
        $filterArray = array_merge(
            explode('&', $decodedFilter),
            explode('&', $extraFilter)
        );

        foreach ($filterArray as $condition) {
            $selectedOperator = null;
            $parsedCondition = self::parseExpr($condition);
            if ($parsedCondition !== null) {
                $cache[] = $parsedCondition;
            }
        }
        $conditionsByField = [];
        foreach ($cache as $condition) {
            $conditionsByField[$condition[0]][] = [$condition[1], $condition[2]];
        }

        $conditions = [];
        foreach ($conditionsByField as $field => $cond) {
            foreach ($cond as $index => $value) {
                $conditions[$index][$field] = $value;
            }
        }

        return $conditions;
    }

    /**
     * @param $string
     */
    public static function parseSorts($string): array
    {
        $sorts = [];
        $sort = explode(',', $string);

        foreach($sort as $item) {
            $criteria   = $item[0] === '-' ? 'DESC' : 'ASC';
            $path       = $item[0] === '-' ? substr($item, 1) : $item;
            $field      = explode('.', $path);

            $sorts[$path] = new FilterOrder(end($field), $criteria);
        }
        return $sorts;
    }

    /**
     * @param $expr
     * @return array|null
     */
    protected static function parseExpr($expr): ?array
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

            $field = $name . (!$name ? '' : '.') . $childName;

            /**
             * Рекурсия на потомков
             */
            if ($config->hasOption('shared_keys')) {

                $sharedKeys = $config->getOption('shared_keys');

                $options = array_replace(
                    $config->getOptions(),
                    ['add_shared' => self::getSharedFilter($sharedKeys[0], $sharedKeys[1] . "_$index")]
                );

                $form->add($childName, get_class($childType), $options);

                $child = $form->get($childName);

                $this->prepareFormField(
                    $childType instanceof CollectionAdapterFilterType ? $child->get(0) : $child,
                    $index,
                    $field,
                    $alias . $sharedKeys[1] . "_$index"
                );

                if ($child->count() === 0) {
                    $form->remove($childName);
                    continue;
                }
                /**
                 * Самый младший ребенок
                 */
            } else {

                if (
                    !array_key_exists($field, $this->values) &&
                    !array_key_exists($field, $this->sorts)
                ) {
                    $form->remove($childName);
                    continue;
                }

                if (array_key_exists($field, $this->sorts)) {
                    $this->sorts[$field]->setAlias($alias);
                }

                if (
                    array_key_exists($field, $this->operators) &&
                    array_key_exists($field, $this->values)
                ) {
                    $isOneSide = in_array($this->operators[$field], self::OPERATORS_ONE_SIDE);
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
                            case self::OPERATOR_IN:
                            case self::OPERATOR_JSON_ARRAY:
                            case self::OPERATOR_JSON_IN:
                            case self::OPERATOR_INSTANCE_OF_IN:
                            {
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
                            case self::OPERATOR_BETWEEN:
                            {
                                if (count($mValue) < 2) {
                                    break;
                                }
                                switch (get_class($childType)) {
                                    case DateTimeFilterType::class:
                                    {
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
                                    case DateFilterType::class:
                                    {
                                        $type = DateRangeFilterType::class;
                                        $options = [
                                            'left_date_options'     => $config->getOptions(),
                                            'right_date_options'    => $config->getOptions()
                                        ];
                                        $this->values[$field] = [
                                            'left_date'             => $mValue[0],
                                            'right_date'            => $mValue[1]
                                        ];
                                        break;
                                    }
                                    case NumberFilterType::class:
                                    {
                                        $type = NumberRangeFilterType::class;
                                        $options = [
                                            'left_number_options' => array_merge(
                                                $config->getOptions(),
                                                ['condition_operator' => FilterOperands::OPERATOR_GREATER_THAN_EQUAL]
                                            ),
                                            'right_number_options' => array_merge(
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

                    if ($applyFilter) {
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
        return function (QueryInterface $filterQuery, $field, $values) use ($comparison) {
            if (empty($values['value'])) {
                return null;
            }
            
            $paramName = sprintf('p_%s', str_replace('.', '_', $field));

            $paramValue = $values['value'];
            $expr = $field . ' ' . $comparison;
            $param = [];

            if (in_array($comparison, self::OPERATORS_TWO_SIDES)) {
                $rvalue = ':' . $paramName;

                switch ($comparison) {
                    case self::OPERATOR_LIKE: {
                        $paramValue = mb_strtolower($paramValue, 'UTF-8');
                        $paramValue = "%$paramValue%";

                        $expr = new Comparison(
                            "LOWER($field)",
                            'LIKE',
                            $rvalue
                        );

                        $param = [$paramName => $paramValue];

                        break;
                    }
                    case self::OPERATOR_JSON_ARRAY: {

                        $count  = 0;
                        $values = $paramValue;
                        foreach ($values as $value) {
                            if ($count == 0) {
                                $paramValue = "{" . $value;
                            } else {
                                $paramValue .= ", " . $value;
                            }
                            $count++;
                        }
                        $paramValue .= "}";

                        $expr = new Comparison(
                            JsonbExistAnyFunction::name . "(" . ToJsonbFunction::name . "(" . $field . "), ",
                            '',
                            " " . $rvalue . ") = true"
                        );

                        $param = [$paramName => $paramValue];

                        break;
                    }
                    case self::OPERATOR_JSON_IN: {

                        $jField = array_shift($paramValue);
                        $jField = explode('.', $jField);
                        $jField = '\'{'.implode(',', $jField).'}\'';

                        $expr = new Comparison(
                            JsonGetPathText::FUNCTION_NAME . "(" . $field . ", $jField )",
                            self::OPERATOR_IN,
                            '(' . $rvalue . ')'
                        );

                        $param = [
                            $paramName => [$paramValue, Connection::PARAM_STR_ARRAY]
                        ];

                        break;
                    }
                    case self::OPERATOR_JSON_IS_NULL:
                    case self::OPERATOR_JSON_IS_NOT_NULL: {

                        $jField = explode('.', $paramValue);
                        $jField = '\'{'.implode(',', $jField).'}\'';

                        $expr = new Comparison(
                            JsonGetPathText::FUNCTION_NAME . "(" . $field . ", $jField )",
                            $comparison == self::OPERATOR_JSON_IS_NULL ? self::OPERATOR_IS_NULL : self::OPERATOR_IS_NOT_NULL,
                            ''
                        );

                        break;
                    }
                    case self::OPERATOR_IN: {
                        $expr = new Comparison(
                            $field,
                            $comparison,
                            '(' . $rvalue . ')'
                        );

                        $param = [
                            $paramName => [$paramValue, Connection::PARAM_STR_ARRAY]
                        ];

                        break;
                    }
                    case self::OPERATOR_INSTANCE_OF: {
                        $parts = explode('.', $field);
                        array_pop($parts);

                        $expr = new Comparison(
                            implode('.', $parts),
                            ' INSTANCE OF ',
                            $paramValue
                        );


                        break;
                    }
                    case self::OPERATOR_INSTANCE_OF_IN: {
                        $parts = explode('.', $field);
                        array_pop($parts);

                        $comparisons = [];

                        $expr = new Expr\Orx();

                        foreach ($paramValue as $discriminator) {
                            $expr->add(new Comparison(
                                implode('.', $parts),
                                ' INSTANCE OF ',
                                $discriminator
                            ));
                        }


                        break;
                    }
                    default: {
                        $expr = new Comparison($field, $comparison, $rvalue);
                        $param = [$paramName => $paramValue];
                    }
                }
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
                $filterBuilder->leftJoin($alias . '.' . $newName, $joinAlias);
            };

            $qbe->addOnce($qbe->getAlias() . '.' . $newName, $qbe->getAlias() . $newAlias, $closure);
        };
    }
}