<?php

namespace Repregid\ApiBundle\Service\Search;

/**
 * Class SphinxIndex
 * @package Repregid\ApiBundle\Service\Search
 */
class SphinxIndex
{
    /**
     * @var SearchEngineInterface
     */
    public $searchEngine;

    /**
     * SphinxIndex constructor.
     * @param SearchEngineInterface $searchEngine
     */
    public function __construct(SearchEngineInterface $searchEngine)
    {
        $this->searchEngine = $searchEngine;
    }

    /**
     * @param string $class
     * @return string
     */
    public function build(string $class)
    {
        return $this->searchEngine->buildIndex($class);
    }
}