<?php

namespace Repregid\ApiBundle\Service\DataFilter\Form\Type;

use Repregid\ApiBundle\Service\DataFilter\Filter;
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

/**
 * Class FilterFormType
 * @package Repregid\ApiBundle\Service\DataFilter\Form
 */
class FilterType extends AbstractType
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
            ->add('filter', $options['filterType'], [
                'allow_extra_fields'    => true,
                'mapped'                => false
            ])
            ->add('sort', UnstructuredType::class, [
            ])
            ->add('index',IntegerType::class, [
                'empty_data' => (string)0
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
            'data_class'            => Filter::class,
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

        !array_key_exists('filter', $filter)        && $filter['filter'] = '';
        $index = $filter['index'] ?? 0;

        $filterService = new FilterService(
            $filter['filter'],
            $filter['sort']
        );

        $filterService->prepareFormField($form->get('filter'), $index);

        $filter['sort']     = $filterService->getSorts();
        $filter['filter']   = $filterService->getNestedValues();

        $event->setData($filter);
    }
}