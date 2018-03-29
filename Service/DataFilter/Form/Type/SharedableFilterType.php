<?php

namespace Repregid\ApiBundle\Service\DataFilter\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

/**
 * Class SharedableFilterType
 * @package Repregid\ApiBundle\Service\DataFilter\Form\Type
 */
class SharedableFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'shared_keys' => []
        ));
    }

    public function getParent()
    {
        return Filters\SharedableFilterType::class;
    }
}