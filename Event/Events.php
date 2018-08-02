<?php

namespace Repregid\ApiBundle\Event;

/**
 * Class Events
 * @package Repregid\ApiBundle\Event
 */
class Events
{
    const EVENT_PREFIX_EXTRA_FILTER = 'repregid_api.extraFilter.pre_set.';

    /**
     * @param $context
     * @return string
     */
    public static function getExtraFilterEventName($context)
    {
        return self::EVENT_PREFIX_EXTRA_FILTER.$context;
    }
}