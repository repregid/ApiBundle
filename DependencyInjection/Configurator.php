<?php

namespace Repregid\ApiBundle\DependencyInjection;

/**
 * Class Configurator
 * @package Repregid\ApiBundle\DependencyInjection
 */
class Configurator
{
    /**
     * @var array
     */
    protected $entityPaths = [];

    /**
     * @var string[]
     */
    protected $actionPaths = [];

    /**
     * @var array
     */
    protected $contexts = [];

    /**
     * @var string
     */
    protected $defaultActions;

    /**
     * @var boolean
     */
    protected $listWithSoftDeleteable;

    /**
     * Configurator constructor.
     *
     * @param $entityPaths
     * @param $actionPaths
     * @param $contexts
     * @param $defaultActions
     * @param $listWithSoftDeleteable
     */
    public function __construct(
        $entityPaths,
        $actionPaths,
        $contexts,
        $defaultActions,
        $listWithSoftDeleteable
    ) {
        $this->entityPaths              = $entityPaths;
        $this->actionPaths              = $actionPaths;
        $this->contexts                 = $contexts;
        $this->defaultActions           = $defaultActions;
        $this->listWithSoftDeleteable   = $listWithSoftDeleteable;
    }

    /**
     * @return array
     */
    public function getEntityPaths(): array
    {
        return $this->entityPaths;
    }

    /**
     * @param array $entityPaths
     * @return $this
     */
    public function setEntityPaths($entityPaths)
    {
        $this->entityPaths = $entityPaths;

        return $this;
    }

    /**
     * @return array
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    /**
     * @param array $contexts
     * @return $this
     */
    public function setContexts($contexts)
    {
        $this->contexts = $contexts;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getActionPaths(): array
    {
        return $this->actionPaths;
    }

    /**
     * @param string[] $actionPaths
     * @return $this
     */
    public function setActionPaths($actionPaths)
    {
        $this->actionPaths = $actionPaths;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultActions(): string
    {
        return $this->defaultActions;
    }

    /**
     * @param string $defaultActions
     * @return $this
     */
    public function setDefaultActions($defaultActions)
    {
        $this->defaultActions = $defaultActions;

        return $this;
    }

    /**
     * @return bool
     */
    public function isListWithSoftDeleteable(): bool
    {
        return $this->listWithSoftDeleteable;
    }

    /**
     * @param bool $listWithSoftDeleteable
     * @return Configurator
     */
    public function setListWithSoftDeleteable(bool $listWithSoftDeleteable)
    {
        $this->listWithSoftDeleteable = $listWithSoftDeleteable;

        return $this;
    }

}