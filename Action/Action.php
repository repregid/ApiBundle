<?php

namespace Repregid\ApiBundle\Action;

/**
 * Class Action
 * @package Repregid\ApiBundle\Action
 */
class Action
{
    const ACTION_LIST       = 'list';
    const ACTION_VIEW       = 'view';
    const ACTION_CREATE     = 'create';
    const ACTION_UPDATE     = 'update';
    const ACTION_DELETE     = 'delete';

    const GROUP_SUFFIX_LIST     = 'list';
    const GROUP_SUFFIX_DETAIL   = 'detail';

    /**
     * @return array
     */
    public static function getDefaultActions()
    {
        return [
            self::ACTION_LIST,
            self::ACTION_VIEW,
            self::ACTION_CREATE,
            self::ACTION_UPDATE,
            self::ACTION_DELETE
        ];
    }
}