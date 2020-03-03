<?php

namespace Repregid\ApiBundle\Event;

use Repregid\ApiBundle\Service\DataFilter\ResultProvider;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ListPostResultEvent
 * @package Repregid\ApiBundle\Event
 */
class ListPostResultEvent extends Event
{
    /**
     * @var string
     */
    protected $entity = '';

    /**
     * @var ResultProvider
     */
    protected $result;

    /**
     * ListPostResultEvent constructor.
     * @param string $entity
     * @param ResultProvider $result
     */
    public function __construct(string $entity, ResultProvider $result)
    {
        $this->entity = $entity;
        $this->result = $result;
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
    public function setEntity($entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return ResultProvider
     */
    public function getResult(): ResultProvider
    {
        return $this->result;
    }

    /**
     * @param ResultProvider $result
     * @return ListPostResultEvent
     */
    public function setResult(ResultProvider $result): self
    {
        $this->result = $result;

        return $this;
    }
}