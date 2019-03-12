<?php

namespace Repregid\ApiBundle\Annotation;

/**
 * Class APIEntity
 * @package Repregid\ApiBundle\Annotation
 * @Annotation
 */
class APIEntity
{
    /**
     * @var APIContext []
     */
    protected $contexts = [];

    /**
     * @var string
     */
    protected $formType = '';

    /**
     * @var string
     */
    protected $filterType = '';

    /**
     * @var APISubList []
     */
    protected $subLists = [];

    /**
     * Флаг, обозначающий какой набор записей будет отдаваться: с мертвыми записями или без.
     * Если не указан,  берется дефолтное с конфига.
     *
     * @var bool
     */
    protected $listWithSoftDeleteable;

    /**
     * @var string
     */
    protected $softDeleteableFieldName;

    /**
     * APIEntity constructor.
     * @param $values
     */
    public function __construct($values)
    {
        if (!isset($values['contexts']) || !is_array($values['contexts'])) {
            throw new \InvalidArgumentException('You must define a "contexts" attribute for each APIEntity annotation. And it must be an array.');
        }

        $this->contexts                 = $values['contexts'];
        $this->formType                 = $values['formType'] ?? '';
        $this->filterType               = $values['filterType'] ?? '';
        $this->listWithSoftDeleteable   = $values['listWithSoftDeleteable'] ?? null;
    }

    /**
     * @return array|APIContext[]
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    /**
     * @return string
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

    /**
     * @return string
     */
    public function getFilterType(): string
    {
        return $this->filterType;
    }

    /**
     * @return APISubList[]
     */
    public function getSubLists(): array
    {
        return $this->subLists;
    }

    /**
     * @param APISubList[] $subLists
     * @return $this
     */
    public function setSubLists($subLists)
    {
        $this->subLists = $subLists;

        return $this;
    }

    /**
     * @param APISubList $subList
     * @return $this
     */
    public function addSubList(APISubList $subList)
    {
        $this->subLists[] = $subList;

        return $this;
    }


    /**
     * @return bool|null
     */
    public function getListWithSoftDeleteable()
    {
        return $this->listWithSoftDeleteable;
    }

    /**
     * @return string
     */
    public function getSoftDeleteableFieldName()
    {
        return $this->softDeleteableFieldName;
    }

    /**
     * @param string $softDeleteableFieldName
     * @return APIEntity
     */
    public function setSoftDeleteableFieldName(string $softDeleteableFieldName): self
    {
        $this->softDeleteableFieldName = $softDeleteableFieldName;

        return $this;
    }
}