<?php

namespace Repregid\ApiBundle\Service\DataFilter\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UnstructuredType
 * @package Repregid\ApiBundle\Service\DataFilter\Form\Type
 */
class UnstructuredType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
            'multiple' => true,
        ));
    }
}