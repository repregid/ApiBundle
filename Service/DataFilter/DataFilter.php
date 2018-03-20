<?php

namespace Repregid\ApiBundle\Service\DataFilter;

use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DataFilter
 * @package Repregid\ApiBundle\Service\DataFilter
 */
class DataFilter
{
    const OPERATOR_NOT_IN = 'NOT IN';
    const OPERATOR_IN = 'IN';
    const OPERATOR_LIKE = '=?';
    const OPERATOR_IS_NULL = 'IS NULL';
    const OPERATOR_IS_NOT_NULL = 'IS NOT NULL';

    const OPERATORS_COMPLEX = [self::OPERATOR_IS_NULL, self::OPERATOR_IS_NOT_NULL, self::OPERATOR_IN, self::OPERATOR_IS_NOT_NULL];
    const OPERATORS_SIMPLE = ['<>', '<=', '>=', '>', '<', self::OPERATOR_LIKE, '='];

    static private $availableOperators;

    /**
     * @var FilterCondition[]
     */
    protected $where = [];

    /**
     * @var FilterOrder[]
     */
    protected $order = [];

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $pageSize;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $stopFields = [];

    /**
     * @param string $filterStr
     * @param string $sortStr
     * @param int|null $page
     * @param int|null $pageSize
     * @param string $query
     * @param array $stopFields
     * @return DataFilter
     */
    public static function create(
        string $filterStr = '',
        string $sortStr = '',
        int $page = null,
        int $pageSize = null,
        string $query = '',
        array $stopFields = []
    ): DataFilter
    {
        return new static($filterStr, $sortStr, $page, $pageSize, $query, $stopFields);
    }

    /**
     * DataFilter constructor.
     *
     * @param $filterStr
     * @param $sortStr
     * @param $page
     * @param $pageSize
     * @param $query
     * @param $stopFields
     */
    protected function __construct(
        string $filterStr = '',
        string $sortStr = '',
        int $page = null,
        int $pageSize = null,
        string $query = '',
        array $stopFields = []
    ) {
        $this->stopFields = $stopFields;

        if (!empty($filterStr)) {
            $this->parseFilter($filterStr);
        }
        if (!empty($sortStr)) {
            $this->parseSort($sortStr);
        }
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->query = $query;
    }

    /**
     * @param Request $request
     * @return DataFilter
     */
    public static function createFromRequest(Request $request): DataFilter
    {
        $filter     = $request->get('filter', '');
        $sort       = $request->get('sort', '');
        $page       = $request->get('page', null);
        $pageSize   = $request->get('pageSize', null);
        $query      = $request->get('query', '');

        return static::create($filter, $sort, $page, $pageSize, $query);
    }

    /**
     * @return array
     */
    protected static function getAvailableOperators(): array
    {
        if (self::$availableOperators === null) {
            self::$availableOperators = array_merge(static::OPERATORS_SIMPLE, static::OPERATORS_COMPLEX);
        }
        return self::$availableOperators;
    }

    /**
     * @param $filter
     */
    protected function parseFilter($filter)
    {
        $decodedFilter = urldecode($filter);
        $filterArray = explode('&', $decodedFilter);

        foreach ($filterArray as $condition) {
            $selectedOperator = null;

            $parsedCondition = $this->parseExpr($condition);

            if ($parsedCondition !== null) {

                $this->where[] = $parsedCondition;
            }
        }
    }

    /**
     * @param $expr
     * @return null|FilterCondition
     */
    protected function parseExpr($expr): ?FilterCondition
    {
        foreach (self::getAvailableOperators() as $operator) {
            $pattern = preg_quote($operator);
            if (!in_array($operator, static::OPERATORS_SIMPLE)) {
                $pattern = "\\s$pattern\\b";
            }
            $pattern = "/$pattern/";
            $dividedExpr = preg_split($pattern, $expr);
            if (in_array($dividedExpr[0], $this->stopFields)) {
                continue;
            }
            if (count($dividedExpr) === 2) {
                $value = trim($dividedExpr[1]);
                if ($operator === static::OPERATOR_IN || $operator === static::OPERATOR_NOT_IN) {
                    $value = $this->parseMultiValue($value);
                }
                if ($operator === static::OPERATOR_LIKE) {
                    $operator = 'LIKE';
                    $value = "%$value%";
                }

                return new FilterCondition($dividedExpr[0], $operator, $value);
            }
        }
        return null;
    }

    /**
     * @param $sort
     */
    protected function parseSort($sort): void
    {
        $sort = urldecode($sort);
        $sortArray = explode(',', $sort);
        foreach ($sortArray as $field) {
            if (strlen($field) === 0) {
                continue;
            }
            $order = Criteria::ASC;
            if ($field[0] === '-') {
                $order = Criteria::DESC;
                $field = substr($field, 1);
            }
            $this->addOrder($field, $order);
        }
    }

    /**
     * @param $value
     * @return array
     */
    public function parseMultiValue($value): array
    {
        try {
            $value = explode(',', $value);
            $result = array_map(function ($v) {
                return trim($v);
            }, $value);
        } catch (\Exception $e) {
            $result = [];
        }

        return $result;
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     * @return DataFilter
     */
    public function addCondition($field, $operator, $value): DataFilter
    {
        $this->where[] = new FilterCondition($field, $operator, $value);

        return $this;
    }

    /**
     * @param $removedCondition
     * @return bool
     */
    public function removeCondition($removedCondition): bool
    {
        foreach ($this->where as $key => $condition) {
            if ($condition == $removedCondition) {
                unset($this->where[$key]);
                return true;
            }
        }

        return false;
    }

    /**
     * @param $field
     * @param $order
     */
    public function addOrder($field, $order): void
    {
        if (!isset($this->order[$field])) {
            $this->order[] = new FilterOrder($field, $order);
        }
    }

    /**
     * @param $field
     * @return DataFilter
     */
    public function removeOrder($field): DataFilter
    {
        if (isset($this->order[$field])) {
            unset($this->order[$field]);
        }

        return $this;
    }

    /**
     * @return FilterCondition[]
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * @return FilterOrder[]
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @param $page
     * @return $this
     */
    public function setPage($page): DataFilter
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return int
     */
    public function getPage(): ?int
    {
        return $this->page;
    }

    /**
     * @param $pageSize
     * @return DataFilter
     */
    public function setPageSize($pageSize): DataFilter
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize(): ?int
    {
        return $this->pageSize;
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