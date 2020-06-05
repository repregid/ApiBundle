<?php

namespace Repregid\ApiBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class ExtraFilterEvent
 */
class ExtraFilterFormEvent extends Event
{
    /**
     * @var string
     */
    protected $entity = '';

    /**
     * @var string
     */
    protected $extraFilter = '';

    /**
     * ExtraFilterFormEvent constructor.
     *
     * @param string $entity
     */
    public function __construct(string $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getExtraFilter(): string
    {
        return $this->extraFilter;
    }

    /**
     * @param string $extraFilter
     * @return $this
     */
    public function setExtraFilter($extraFilter)
    {
        $this->extraFilter = $extraFilter;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @param string $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }
}