<?php

namespace Repregid\ApiBundle\Service\DataFilter;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
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
    public static function createResultsProvider(QueryBuilder $qb, CommonFilter $filter): ResultProvider
    {
        $pagerQB    = clone $qb;
        $pager      = new Paginator($pagerQB->getQuery());

        return new ResultProvider(
            $pager->getIterator()->getArrayCopy(),
            $pager->count(),
            $filter->getPageSize()
        );
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

        if($pageSize > 0) {
            $qb->setMaxResults($pageSize);
            $qb->setFirstResult(($page - 1) * $pageSize);
        } else {
            $qb->setMaxResults(200);
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
        string $target = null
    ) {
        if($filter->getQuery() && $searchEngine && $target) {
            $ids = $searchEngine->findByTerm($filter->getQuery(), $target);

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