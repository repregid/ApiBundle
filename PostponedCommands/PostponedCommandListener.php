<?php

namespace Repregid\ApiBundle\PostponedCommands;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PostponedCommandListener
 * @package Repregid\ApiBundle\PostponedCommands
 */
class PostponedCommandListener implements EventSubscriberInterface
{
    /**
     * @var CommandHandler
     */
    private $commandHandler;

    /**
     * ApplyCommandsTerminateListener constructor.
     * @param CommandHandler $commandHandler
     */
    public function __construct(CommandHandler $commandHandler)
    {
        $this->commandHandler = $commandHandler;
    }

    /**
     * @param PostResponseEvent $PostResponseEvent
     */
    public function Terminate(PostResponseEvent $PostResponseEvent)
    {
        $this->commandHandler->handle();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => [['Terminate']],
        ];
    }
}