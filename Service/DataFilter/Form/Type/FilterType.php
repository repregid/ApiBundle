<?php

namespace Repregid\ApiBundle\Service\DataFilter\Form\Type;

use Repregid\ApiBundle\Service\DataFilter\Filter;
use Repregid\ApiBundle\Service\DataFilter\FilterOrder;
use Repregid\ApiBundle\Service\DataFilter\FilterService;
use Repregid\ApiBundle\Service\DataFilter\Form\DataTransformer\SortTransformer;
use Symfony\Component\Form\AbstractType;
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
            ->add('extraFilter', TextType::class, [
                'mapped' => false
            ])
            ->add('sort', TextType::class, [
                'empty_data' => '-id'
            ])
            ->add('page',IntegerType::class, [
                'empty_data' => (string)Filter::PAGE_DEFAULT
            ])
            ->add('pageSize',IntegerType::class, [
                'empty_data' => (string)Filter::PAGE_SIZE_DEFAULT
            ])
            ->add('query',TextType::class, [
                'empty_data' => (string)Filter::QUERY_DEFAULT
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
            'filterType'            => DefaultFilterType::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return null;
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
        !array_key_exists('sort', $filter)          && $filter['sort'] = '-id';

        $filterService = new FilterService(
            $filter['filter'],
            $filter['sort'],
            $extra->getData() ?: ''
        );

        $filterService->prepareFormField($form->get('filter'));

        $filter['sort']     = $filterService->getSorts();
        $filter['filter']   = $filterService->getValues();

        $event->setData($filter);
    }
}