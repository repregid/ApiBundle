<?php

namespace Repregid\ApiBundle\PostponedCommands\Commands;

use Repregid\ApiBundle\PostponedCommands\PostponedCommandInterface;
use Repregid\ApiSearchSphinx\Sphinx\Sphinx;

/**
 * Class SphinxIndexer
 * @package Repregid\ApiBundle\PostponedCommands\Commands
 */
class SphinxIndexer implements PostponedCommandInterface
{
    /**
     * @var string
     */
    private $index;

    /**
     * @var Sphinx
     */
    private $sphinx;

    /**
     * SphinxIndexer constructor.
     * @param Sphinx $sphinx
     */
    public function __construct(Sphinx $sphinx)
    {
        $this->sphinx = $sphinx;
    }

    /**
     * @param $indexName
     * @return $this
     */
    public function setIndex($indexName)
    {
        $this->index = $this->sphinx
            ->buildIndex($indexName);

        return $this;
    }

    public function run()
    {
        $command = "indexer --rotate " . $this->index;
        exec($command);
    }
}