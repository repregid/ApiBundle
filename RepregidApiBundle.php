<?php

namespace Repregid\ApiBundle;

use Repregid\ApiBundle\Controller\CRUDControllerInterface;
use Repregid\ApiBundle\DependencyInjection\CRUDControllerCompilerPass;
use Repregid\ApiBundle\Service\Search\SearchEngineInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class RepregidApiBundle
 * @package Repregid\ApiBundle
 */
class RepregidApiBundle extends Bundle
{
    /**
     * @param ContainerBuilder $builder
     */
    public function build(ContainerBuilder $builder)
    {
        parent::build($builder);

        $builder->addCompilerPass(new CRUDControllerCompilerPass());

        $builder->registerForAutoconfiguration(SearchEngineInterface::class)
            ->addTag('repregid_api.search_engine');

        $builder->registerForAutoconfiguration(CRUDControllerInterface::class)
            ->addTag('repregid_api.crud_controller');
    }
}