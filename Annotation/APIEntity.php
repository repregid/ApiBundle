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
     * APIEntity constructor.
     * @param $values
     */
    public function __construct(
        array $contexts = [],
        ?string $formType = null,
        ?string $filterType = null,
        ?bool $listWithSoftDeleteable = false)
    {
        $this->contexts                 = $contexts;
        $this->formType                 = $formType;
        $this->filterType               = $filterType;
        $this->listWithSoftDeleteable   = $listWithSoftDeleteable;
    }
}