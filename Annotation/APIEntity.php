<?php

namespace Repregid\ApiBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Class APIEntity
 * @package Repregid\ApiBundle\Annotation
 * @Annotation
 * @NamedArgumentConstructor()
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class APIEntity
{
    /**
     * @var APIContext []
     */
    public $contexts = [];

    /**
     * @var string
     */
    public $formType = '';

    /**
     * @var string
     */
    public $filterType = '';

    /**
     * @var APISubList []
     */
    public $subLists = [];

    /**
     * Флаг, обозначающий какой набор записей будет отдаваться: с мертвыми записями или без.
     * Если не указан,  берется дефолтное с конфига.
     *
     * @var bool|null
     */
    public $listWithSoftDeleteable;

    /**
     * @var string
     */
    public $softDeleteableFieldName;

    /**
     * При выборе списка объектов разрешает передать параметр PageSize=0 и получить все результаты разом
     * @var bool
     */
    public $allowUnlimited = false;

    /**
     * Для больших таблиц выключаем подсчет всех элементов и делаем педженатор бесконечным
     * @var bool
     */
    public $infinitePages = false;

    /**
     * APIEntity constructor.
     * @param array $contexts
     * @param string|null $formType
     * @param string|null $filterType
     * @param bool|null $listWithSoftDeleteable
     * @param bool $allowUnlimited
     * @param bool $infinitePages
     */
    public function __construct(
        array $contexts = [],
        ?string $formType = null,
        ?string $filterType = null,
        ?bool $listWithSoftDeleteable = false,
        bool $allowUnlimited = false,
        bool $infinitePages = false
    ) {
        $this->contexts                 = $contexts;
        $this->formType                 = $formType;
        $this->filterType               = $filterType;
        $this->listWithSoftDeleteable   = $listWithSoftDeleteable;
        $this->allowUnlimited           = $allowUnlimited;
        $this->infinitePages            = $infinitePages;
    }
}