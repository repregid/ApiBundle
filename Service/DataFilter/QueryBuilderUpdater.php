<?php

namespace Repregid\ApiBundle\Service\DataFilter;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Repregid\ApiBundle\Service\Search\SearchEngineInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class QueryBuilderUpdater
 * @package Repregid\ApiBundle\Repository
 */
class QueryBuilderUpdater
{
    /**
     * @param QueryBuilder $qb
     * @param Filter $filter
     * @return ResultProvider
     */
    public static function createResultsProvider(QueryBuilder $qb, CommonFilter $filter, bool $fetchJoinCollection): ResultProvider
    {
        $pagerQB    = clone $qb;
        $pager      = new Paginator($pagerQB->getQuery(), $fetchJoinCollection);

        $totalCount = $filter->isInfinitePages() ? 0 : $pager->count();
        $pageSize   = $filter->getPageSize();

        if ($pageSize <= 0) {
            $pageSize = $filter->isAllowUnlimited()
                ? ($totalCount ?: 1)
                : CommonFilter::PAGE_SIZE_DEFAULT;
        }

        $results = $filter->isInfinitePages()
            ? $qb->getQuery()->getResult()
            : $pager->getIterator()->getArrayCopy();

        return new ResultProvider($results, $totalCount, $pageSize);
    }

    /**
     * @param QueryBuilder $qb
     * @param FormInterface $filter
     * @param FilterBuilderUpdaterInterface $updater
     */
    public static function addFilter(
        QueryBuilder $qb,
        FormInterface $filter,
        FilterBuilderUpdaterInterface $updater
    ) {
        $updater->addFilterConditions($filter, $qb);
    }

    /**
     * @param QueryBuilder $qb
     * @param Filter $filter
     */
    public static function addSorts(QueryBuilder $qb, Filter $filter)
    {
        foreach ($filter->getSort() as $order) {
            if(NULL === $order->getAlias()) {
                continue;
            }

            $field = $order->getAlias().'.'.$order->getField();
            $field = $qb->getRootAliases()[0].(FALSE !== strpos($field, '.') ? '' : '.').$field;

            $qb->addOrderBy($field, $order->getOrder());
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param Filter $filter
     */
    public static function addPaginator(QueryBuilder $qb, CommonFilter $filter)
    {
        $page       = $filter->getPage();
        $pageSize   = $filter->getPageSize();

        if ($pageSize > 0) {
            $qb->setMaxResults($pageSize);
            $qb->setFirstResult(($page - 1) * $pageSize);
        } elseif (!$filter->isAllowUnlimited()) {
            $qb->setMaxResults(CommonFilter::PAGE_SIZE_DEFAULT);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param Filter $filter
     * @param SearchEngineInterface|null $searchEngine
     * @param string|null $target
     */
    public static function addSearch(
        QueryBuilder $qb,
        CommonFilter $filter,
        SearchEngineInterface $searchEngine = null,
        string $target = null,
        array $searchFields = []
    ) {
        if($filter->getQuery() && $searchEngine && $target) {
            $searchResult = $searchEngine->findByTerm($filter->getQuery(), $target, $searchFields);

            $values = [];
            $ids = [];
            foreach ($searchResult as ['idx' => $ind, 'weight' => $weight]) {
                if (!empty($weight)) {
                    $values[] = 'WHEN ' . $qb->getRootAliases()[0] . ".id = $ind THEN $weight";
                }
                $ids[] = $ind;
            }
            if (!empty($values)) {
                $qb->addSelect('(CASE ' . implode(' ', $values) . ' ELSE 0 END) AS HIDDEN weight ');
                $qb->addOrderBy('weight', 'DESC');
            }
            $expr = new Comparison($qb->getRootAliases()[0].'.id', 'IN', "(:valueQuery)");
            $qb->andWhere(new Andx([$expr]));
            $qb->setParameter("valueQuery", $ids);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param $field
     * @param $operator
     * @param $value
     */
    public static function addExtraFilter(QueryBuilder $qb, $field, $operator, $value)
    {
        $expr = new Comparison($qb->getRootAliases()[0].'.'.$field, $operator, $value);
        $qb->andWhere(new Andx([$expr]));
    }
}