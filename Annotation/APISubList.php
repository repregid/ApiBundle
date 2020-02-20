<?php

namespace Repregid\ApiBundle\Annotation;

/**
 * Class APISubList
 * @package Repregid\ApiBundle\Annotation
 * @Annotation
 */
class APISubList
{
    const SIDE_VIEW = 'view';
    const SIDE_LIST = 'list';

    const DEFAULT_ID_REQUIREMENT = '\d+';
    const DEFAULT_ID_NAME        = 'id';

    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $extraField;

    /**
     * @var string
     */
    protected $viewContext = '';

    /**
     * @var string
     */
    protected $listContext = '';

    /**
     * @var string
     */
    protected $side = self::SIDE_VIEW;

    /**
     * @var string
     */
    protected $listClass;

    /**
     * @var string
     */
    protected $viewClass;

    /**
     * @var string
     */
    protected $idRequirement = self::DEFAULT_ID_REQUIREMENT;

    /**
     * @var string
     */
    protected $idName = self::DEFAULT_ID_NAME;


    /**
     * APIParent constructor.
     * @param $values
     */
    public function __construct($values)
    {
        if (empty($values['field']) && empty($values['extraField'])) {
            throw new \InvalidArgumentException('You must define a "field" or "extraField" attribute for each APISubList annotation.');
        }

        if (!isset($values['listContext']) || empty($values['listContext'])) {
            throw new \InvalidArgumentException('You must define a "listContext" attribute for each APISubList annotation.');
        }

        if (!isset($values['viewContext']) || empty($values['viewContext'])) {
            throw new \InvalidArgumentException('You must define a "viewContext" attribute for each APISubList annotation.');
        }

        $this->field            = $values['field'] ?? null;
        $this->extraField       = $values['extraField'] ?? null;
        $this->side             = $values['side']               ?? self::SIDE_VIEW;
        $this->listContext      = $values['listContext'];
        $this->viewContext      = $values['viewContext'];
        $this->idRequirement    = $values['idRequirement']      ?? self::DEFAULT_ID_REQUIREMENT;
        $this->idName           = $values['idName']             ?? self::DEFAULT_ID_NAME;
    }

    /**
     * @return string
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtraField(): ?string
    {
        return $this->extraField;
    }

    /**
     * @param string $extraField
     * @return $this
     */
    public function setExtraField(?string $extraField): self
    {
        $this->extraField = $extraField;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewContext(): string
    {
        return $this->viewContext;
    }

    /**
     * @param string $viewContext
     * @return $this
     */
    public function setViewContext($viewContext)
    {
        $this->viewContext = $viewContext;

        return $this;
    }

    /**
     * @return string
     */
    public function getListContext(): string
    {
        return $this->listContext;
    }

    /**
     * @param string $listContext
     * @return $this
     */
    public function setListContext($listContext)
    {
        $this->listContext = $listContext;

        return $this;
    }

    /**
     * @return string
     */
    public function getListClass(): string
    {
        return $this->listClass;
    }

    /**
     * @param string $listClass
     * @return $this
     */
    public function setListClass($listClass)
    {
        $this->listClass = $listClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getViewClass(): string
    {
        return $this->viewClass;
    }

    /**
     * @param string $viewClass
     * @return $this
     */
    public function setViewClass($viewClass)
    {
        $this->viewClass = $viewClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getSide(): string
    {
        return $this->side;
    }

    /**
     * @param string $side
     * @return $this
     */
    public function setSide($side)
    {
        $this->side = $side;

        return $this;
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
}