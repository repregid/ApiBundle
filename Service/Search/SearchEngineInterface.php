<?php

namespace Repregid\ApiBundle\Service\Search;


/**
 * Interface SearchEngineInterface
 */
interface SearchEngineInterface
{
    /**
     * @param string $term
     * @param string $target
     * @param array $fields
     * @return array Array of Ids
     */
    public function findByTerm(string $term, string $target, array $fields = []) : array ;
}