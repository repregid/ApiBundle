<?php

namespace Repregid\ApiBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class RepregidApiExtension
 * @package Repregid\ApiBundle\DependencyInjection
 */
class RepregidApiExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configurator = $container->getDefinition(Configurator::class);

        $configurator->addArgument($config['entityPaths']);
        $configurator->addArgument($config['actionPaths']);
        $configurator->addArgument($config['contexts']);
        $configurator->addArgument($config['defaultActions']);
        $configurator->addArgument($config['listWithSoftDeleteable']);

        $container->setParameter('repregid_api.controller.crud.class', $config['controller']);

        $controller = $container->getDefinition('repregid_api.controller.crud');

        if($config['searchEngine']) {
            $container->register($config['searchEngine']);
            if (false === $container->hasDefinition($config['searchEngine'])) {
                throw new \Exception("'".$config['searchEngine']."' search engine could not be found!");
            }
            $controller->addMethodCall('setSearchEngine', [new Reference($config['searchEngine'])]);
            $controller->addMethodCall('setIndexPrefix', [$config['indexPrefix']]);
        }
    }

}
