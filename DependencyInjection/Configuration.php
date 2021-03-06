<?php

namespace Repregid\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Repregid\ApiBundle\Action\Action;

/**
 * Class Configuration
 * @package Repregid\ApiBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('repregid_api');
        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // symfony < 4.2 support
            $rootNode = $treeBuilder->root('repregid_api');
        }

        $rootNode
            ->children()
                ->arrayNode('entityPaths')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('actionPaths')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('contexts')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('url')->end()
                            ->arrayNode('actions')
                                ->scalarPrototype()->end()
                                ->defaultValue(Action::getDefaultActions())
                            ->end()
                            ->arrayNode('security')
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->scalarPrototype()->end()
                                ->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('defaultActions')->defaultValue('@RepregidApiBundle/Resources/config/actions.yml')->end()
                ->scalarNode('controller')->defaultValue('Repregid\ApiBundle\Controller\CRUDController')->end()
                ->scalarNode('searchEngine')->defaultValue(null)->end()
                ->booleanNode('postponedCommands')->defaultValue(false)->end()
                ->booleanNode('listWithSoftDeleteable')->defaultValue(false)
            ->end()
        ;

        return $treeBuilder;
    }
}
