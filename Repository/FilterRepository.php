<?php

namespace Repregid\ApiBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Repregid\ApiBundle\Service\DataFilter\DataFilter;
use Repregid\ApiBundle\Service\DataFilter\FilterCondition;
use Repregid\ApiBundle\Service\Search\SearchEngineInterface;

/**
 * Class AbstractRepository
 * @package Repregid\ApiBundle\Repository
 */
class FilterRepository extends EntityRepository
{
    /**
     * default alias of table
     */
    const ALIAS = 't';

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var int
     */
    protected $pageSize = 20;

    /**
     * @var array
     */
    protected $ordering = ['id' => Criteria::DESC];

    /**
     * @var SearchEngineInterface
     */
    protected $searchEngine;

    /**
     * @return SearchEngineInterface
     */
    public function getSearchEngine(): ?SearchEngineInterface
    {
        return $this->searchEngine;
    }

    /**
     * @param SearchEngineInterface $searchEngine
     * @return $this
     */
    public function setSearchEngine(SearchEngineInterface $searchEngine = null)
    {
        $this->searchEngine = $searchEngine;

        return $this;
    }

    /**
     * @param FilterCondition $condition
     * @return bool
     */
    protected function beforeApplyFilterCondition(FilterCondition $condition): bool
    {
        return true;
    }

    /**
     * @param array $results
     * @param int $totalCount
     * @param int $pageSize
     * @return ResultProvider
     */
    public function createResultsProvider(array $results, int $totalCount, int $pageSize): ResultProvider
    {
        return new ResultProvider($results, $totalCount, $pageSize);
    }

    /**
     * @param DataFilter $filter
     * @return ResultProvider
     */
    public function findByFilter(DataFilter $filter): ResultProvider
    {
        $qb = $this->createQueryBuilder(self::ALIAS);
        $addedJoins = [];

        /**
         * Разбирает свойство, затем возвращает его верное имя с префиксом.
         * Если свойство вложенное, то создает необходимые объединения таблиц.
         * @param $field
         * @return string
         */
        $extractField = function($field) use ($qb, &$addedJoins) {
            $dividedField = explode('.', $field);
            $partFieldCount = count($dividedField);
            $fieldPrefix = self::ALIAS;
            $fieldName = $dividedField[$partFieldCount - 1];

            if($partFieldCount > 1) {
                for($i = 0; $i < $partFieldCount - 1; $i++) {
                    if(in_array($dividedField[$i], $addedJoins) === false) {
                        $joinPrefix = $i ? $dividedField[$i - 1] : self::ALIAS;

                        $column = $dividedField[$i];
                        if ($qb->getEntityManager()->getConnection()->getDatabasePlatform()->getReservedKeywordsList()->isKeyword($dividedField[$i])) {
                            $dividedField[$i] = $dividedField[$i] . '1';
                        }
                        $qb->innerJoin(
                            $joinPrefix . '.' . $column,
                            $dividedField[$i]
                        );

                        $addedJoins[] = $dividedField[$i];
                    }
                }

                $fieldPrefix = $dividedField[$partFieldCount - 2];
            }

            return $fieldPrefix . "." . $fieldName;
        };

        /** условные операторы */
        foreach($filter->getWhere() as $key => $condition) {
            $this->beforeApplyFilterCondition($condition);
            $fieldName = $extractField($condition->getField());

            $rightExpr = is_array($condition->getValue()) ? "(:value$key)" : ":value$key";

            if (stripos($condition->getOperator(),'IS NULL') !== false)
            {
                $qb->andWhere(new Andx([$qb->expr()->isNull($fieldName)]));
            }
            else if (stripos($condition->getOperator(),'IS NOT NULL') !== false)
            {
                $qb->andWhere(new Andx([$qb->expr()->isNotNull($fieldName)]));
            }
            else
            {
                $expr = new Comparison($fieldName, $condition->getOperator(), $rightExpr);
                $qb->andWhere(new Andx(array($expr)));
                $qb->setParameter("value$key", $condition->getValue());
            }
        }

        if($filter->getQuery() && $this->searchEngine) {
            $ids = $this->searchEngine->findByTerm($filter->getQuery(), $this->_entityName);

            $expr = new Comparison(self::ALIAS.'.id', 'IN', "(:valueQuery)");
            $qb->andWhere(new Andx([$expr]));
            $qb->setParameter("valueQuery", $ids);
        }

        $pagerQueryBuilder = clone $qb;

        /** операторы сортировки */
        $orderings = array();
        foreach($filter->getOrder() as $orderCondition) {
            $orderings[$orderCondition->getField()] = $orderCondition->getOrder();
        }

        if(count($orderings) === 0) {
            $orderings = $this->ordering;
        }

        foreach($orderings as $field => $order) {
            $fieldName = $extractField($field);
            $qb->addOrderBy($fieldName, $order);
        }

        /** пагинация */
        $pageSize = $filter->getPageSize() ?: $this->pageSize;
        if($pageSize > 200) {
            $pageSize = 200;
        }
        $qb->setMaxResults($pageSize);

        $page = $filter->getPage() ?: $this->page;
        $qb->setFirstResult(($page - 1) * $pageSize);

        $query = $pagerQueryBuilder->getQuery();
        $pager = new Paginator($query);
        $totalCount = $pager->count();

        $results = $qb->getQuery()->getResult();

        return $this->createResultsProvider($results, $totalCount, $pageSize);
    }
}