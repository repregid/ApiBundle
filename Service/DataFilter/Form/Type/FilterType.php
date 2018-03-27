<?php

namespace Repregid\ApiBundle\Service\DataFilter\Form\Type;

use Repregid\ApiBundle\Service\DataFilter\Filter;
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
        if(!$options['filterType']) {
            throw new \Exception('filterType not found!');
        }

        /**
         * FIELDS
         */
        $builder
            ->add('filter', $options['filterType'], [
                'allow_extra_fields'    => true,
                'mapped'                => false
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
        $builder->get('filter')->addEventListener(
            FormEvents::PRE_SUBMIT, [$this, 'filterListener']
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT, [$this, 'sortsListener']
        );

        /**
         * TRANSFORMERS
         */
        $builder->get('sort')->addModelTransformer(new SortTransformer());
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'            => Filter::class,
            'allow_extra_fields'    => true,
            'filterType'            => null
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

        $filterService = new FilterService();

        $values     = $filterService->parseString($filter);
        $operators  = $filterService->parseString($filter, true);

        $filterService::prepareForm($form, $operators);

        $event->setData($values);
    }

    /**
     * @param FormEvent $event
     */
    public function sortsListener(FormEvent $event)
    {
        $data   = $event->getData();
        $form   = $event->getForm();
        $sorts  = $data->getSort();

        $filterSorts = $form->get('filter')->getConfig()->getOption('sorts', []);

        foreach($sorts as $key => $sort) {
            if(!isset($filterSorts[$sort->getField()])) {
                unset($sorts[$key]);
            } elseif(is_string($filterSorts[$sort->getField()])) {
                $sort->setAlias($filterSorts[$sort->getField()]);
            }
        }

        if(empty($sorts)) {
            $sorts = Filter::getDefaultSort();
        }

        $data->setSort($sorts);
    }
}