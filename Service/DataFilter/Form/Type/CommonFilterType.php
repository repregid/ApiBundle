<?php

namespace Repregid\ApiBundle\Service\DataFilter\Form\Type;

use Repregid\ApiBundle\Service\DataFilter\CommonFilter;
use Repregid\ApiBundle\Service\DataFilter\FilterOrder;
use Repregid\ApiBundle\Service\DataFilter\FilterService;
use Repregid\ApiBundle\Service\DataFilter\Form\DataTransformer\SortTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Class ResultProviderType
 * @package Repregid\ApiBundle\Service\DataFilter\Form
 */
class CommonFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * FIELDS
         */
        $builder
            ->add('page',IntegerType::class, [
                'empty_data' => (string)CommonFilter::PAGE_DEFAULT,
                'constraints' => [new Range(['min' => 1])]
            ])
            ->add('pageSize',IntegerType::class, [
                'empty_data' => (string)CommonFilter::PAGE_SIZE_DEFAULT
            ])
            ->add('query',TextType::class, [
                'empty_data' => (string)CommonFilter::QUERY_DEFAULT
            ])
            ->add('sort', UnstructuredType::class, [
            ])
            ->add('extraFilter', TextType::class, [
                'mapped' => false
            ])
            ->add('filter', UnstructuredType::class, [
            ])
        ;

        /**
         * LISTENERS
         */
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT, [$this, 'filterListener']
        );
    }
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'            => CommonFilter::class,
            'allow_extra_fields'    => true,
            'filterType'            => DefaultFilterType::class,
            'csrf_protection'       => false
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * @param FormEvent $event
     */
    public function filterListener(FormEvent $event)
    {
        $filter = $event->getData();
        $form   = $event->getForm();
        $extra  = $form->get('extraFilter');

        !array_key_exists('filter', $filter)        && $filter['filter'] = '';
        !array_key_exists('sort', $filter)        && $filter['sort'] = '-id';

        $filter['filter'] = FilterService::parseFilters($filter['filter'], $extra->getData() ?: '');
        $filter['sort'] = FilterService::parseSorts($filter['sort']);

        //Если не спарсилось ниодной формы фильтрации - добавляем одну, но пустую
        if (empty($filter['filter'])) {
            $filter['filter'] = [[]];
        }

        $event->setData($filter);
    }

}