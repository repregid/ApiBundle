<?php

namespace Repregid\ApiBundle\Annotation;

/**
 * Class APIContext
 * @package Repregid\ApiBundle\Annotation
 * @Annotation
 */
class APIContext
{
    const DEFAULT_ID_REQUIREMENT = '\d+';
    const DEFAULT_ID_NAME        = 'id';

    /**
     * @var string
     */
    protected $uri;

    /**
     * Набор экшенов, если они отличаются от дефолтных
     * ["list", "view"]         - будут только list и view
     * ["default", "register"]  - будут все дефолтные + register
     *
     * @var array
     */
    protected $actions;

    /**
     * Кастомизирует FormType для типозависимых экшенов
     * [
     *  "all"       => "\App\Fom\Type\CustomType", - для всех типозависимых экшенов
     *  "create"    => "\App\Fom\Type\CustomType", - конкретно для экшена create
     * ]
     *
     * @var array
     */
    protected $types = [];

    /**
     * Кастомизирует Группы сериализации для экшенов
     * [
     *  "all"       => ["default", "custom2"], - для всех экшенов дефолтные + custom2
     *  "create"    => ["custom1", "custom2"], - для create только custom1 и custom2
     *  "update"    => ["default", "custom1"], - для update все дефолтные + custom1
     * ]
     *
     * @var array
     */
    protected $serializationGroups = [];

    /**
     * Массив атрибутов для метода isGranted
     * Так же поддерживаются атрибуты кастомных воттеров, в них будет передан выбранный объект
     *
     * security = {
     *     "create"    = {"ROLE_ADMIN", "OBJECT_CAN_VIEW"},
     * }
     *
     * @var array
     */
    protected $security = [];

    /**
     * Связывание контекстов.
     *
     * В массив передаются именна контекстов с которыми нужно связать текущий.
     * Связывание влияет на дополнительные настройки контекста, например группы сериализации.
     *
     * bindings = {"dictionary"} - связывает текущий контекст с контекстом "admin"
     *
     * Эта связь аналогична добавлению:
     *
     * serializationGroups = {
     *     "list"      = {"default", "dictionary_list"},
     *     "view"      = {"default", "dictionary_detail"},
     *     "create"    = {"default", "dictionary_detail"},
     *     "update"    = {"default", "dictionary_detail"}
     * }
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * @var string
     */
    protected $idRequirement = self::DEFAULT_ID_REQUIREMENT;

    /**
     * @var string
     */
    protected $idName = self::DEFAULT_ID_NAME;

    /**
     * APIEntity constructor.
     * @param $values
     */
    public function __construct($values)
    {
        $this->uri                  = $values['uri']                    ?? null;
        $this->actions              = $values['actions']                ?? [];
        $this->types                = $values['types']                  ?? [];
        $this->serializationGroups  = $values['serializationGroups']    ?? [];
        $this->bindings             = $values['bindings']               ?? [];
        $this->idRequirement        = $values['idRequirement']          ?? self::DEFAULT_ID_REQUIREMENT;
        $this->idName               = $values['idName']                 ?? self::DEFAULT_ID_NAME;
        $this->security             = $values['security']               ?? [];
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @return string
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return array
     */
    public function getSerializationGroups(): array
    {
        return $this->serializationGroups;
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * @return string
     */
    public function getIdRequirement(): string
    {
        return $this->idRequirement;
    }

    /**
     * @return string
     */
    public function getIdName(): string
    {
        return $this->idName;
    }

    /**
     * @return array
     */
    public function getSecurity(): array
    {
        return $this->security;
    }
}