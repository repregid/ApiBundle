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
     * @return array Array ['idx' => itemId, 'weight' => weight]
     */
    public function findByTerm(string $term, string $target, array $fields = []): array;

    /**
     * @param string $entityName
     * @return string
     */
    public function buildIndex($entityName);
}