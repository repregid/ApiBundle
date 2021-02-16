<?php

namespace Repregid\ApiBundle\Service\DataFilter\Form\Exception;

use Symfony\Component\Form\FormInterface;

class FormErrorException extends \Exception
{
    /** @var FormInterface */
    protected $form;

    /**
     * FormErrorException constructor.
     * @param FormInterface $form
     */
    public function __construct(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }
}