<?php

namespace Repregid\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Repregid\ApiBundle\Service\DataFilter\Filter;
use Repregid\ApiBundle\Service\Search\SearchEngineInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class FilterRepository
 * @package Repregid\ApiBundle\Repository
 */
class FilterRepository extends EntityRepository
{
    /**
     * @param QueryBuilder $qb
     * @param Filter $filter
     * @return ResultProvider
     */
    public function createResultsProvider(QueryBuilder $qb, Filter $filter): ResultProvider
    {
        $pagerQB    = clone $qb;
        $pager      = new Paginator($pagerQB->getQuery());

        return new ResultProvider(
            $qb->getQuery()->getResult(),
            $pager->count(),
            $filter->getPageSize()
        );
    }

    /**
     * @param QueryBuilder $qb
     * @param FormInterface $filter
     * @param FilterBuilderUpdaterInterface $updater
     * @return $this
     */
    public function addFilter(
        QueryBuilder $qb,
        FormInterface $filter,
        FilterBuilderUpdaterInterface $updater
    ) {
        $updater->addFilterConditions($filter, $qb);

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param Filter $filter
     * @return $this
     */
    public function addSorts(QueryBuilder $qb, Filter $filter)
    {
        foreach ($filter->getSort() as $order) {
            if(NULL === $order->getAlias()) {
                continue;
            }

            $field = $order->getAlias().'.'.$order->getField();
            $field = $qb->getRootAliases()[0].(FALSE !== strpos($field, '.') ? '' : '.').$field;

            $qb->addOrderBy($field, $order->getOrder());
        }

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param Filter $filter
     * @return $this
     */
    public function addPaginator(QueryBuilder $qb, Filter $filter)
    {
        $page       = $filter->getPage();
        $pageSize   = $filter->getPageSize();

        $qb->setMaxResults($pageSize);
        $qb->setFirstResult(($page - 1) * $pageSize);

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @param Filter $filter
     * @param SearchEngineInterface|null $searchEngine
     * @return $this
     */
    public function addSearch(
        QueryBuilder $qb,
        Filter $filter,
        SearchEngineInterface $searchEngine = null
    ) {
        if($filter->getQuery() && $searchEngine) {
            $ids = $searchEngine->findByTerm($filter->getQuery(), $this->_entityName);

            $expr = new Comparison(self::ALIAS.'.id', 'IN', "(:valueQuery)");
            $qb->andWhere(new Andx([$expr]));
            $qb->setParameter("valueQuery", $ids);
        }

        return $this;
    }
}