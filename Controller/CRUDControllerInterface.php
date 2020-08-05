<?php

namespace Repregid\ApiBundle\Controller;


use Repregid\ApiBundle\Service\Search\SearchEngineInterface;

/**
 * Interface CRUDControllerInterface
 * @package Repregid\ApiBundle\Controller
 */
interface CRUDControllerInterface
{
    /**
     * @param SearchEngineInterface $searchEngine
     * @return mixed
     */
    public function setSearchEngine(SearchEngineInterface $searchEngine);
}