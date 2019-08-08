<?php

namespace Repregid\ApiBundle\PostponedCommands;

/**
 * Class CommandHandler
 * @package Repregid\ApiBundle\PostponedCommands
 */
class CommandHandler
{
    /**
     * @var array
     */
    private $commands = [];

    /**
     * @param PostponedCommandInterface $command
     * @return $this
     */
    public function addCommand(PostponedCommandInterface $command)
    {
        $this->commands[] = $command;

        return $this;
    }

    public function handle()
    {
        foreach ($this->commands as $command) {
            $command->run();
        }
    }
}