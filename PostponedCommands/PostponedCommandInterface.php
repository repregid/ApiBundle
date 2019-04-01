<?php

namespace Repregid\ApiBundle\PostponedCommands;

/**
 * Interface PostponedCommandInterface
 * @package Repregid\ApiBundle\PostponedCommands
 */
interface PostponedCommandInterface
{
    public function run();
}