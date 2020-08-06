<?php

namespace Repregid\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class CRUDControllerCompilerPass
 * @package Repregid\ApiBundle\DependencyInjection
 */
class CRUDControllerCompilerPass implements CompilerPassInterface 
{
    /**
     * @param ContainerBuilder $container
     */
	public function process(ContainerBuilder $container) 
    {
        $taggedServices = $container->findTaggedServiceIds('repregid_api.crud_controller');

        foreach ($taggedServices as $serviceId => $tagAttributes) {
            if ($container->hasParameter('repregid_api.search_engine.class')) {
                $container->getDefinition($serviceId)->addMethodCall(
                    'setSearchEngine', [new Reference($container->getParameter('repregid_api.search_engine.class'))]
                );
            }
        }
	}
}