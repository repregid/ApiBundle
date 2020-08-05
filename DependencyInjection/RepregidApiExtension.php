<?php

namespace Repregid\ApiBundle\DependencyInjection;


use Repregid\ApiBundle\PostponedCommands\PostponedCommandListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

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

        if($config['searchEngine']) {
            $container->register('repregid_api.search_engine', $config['searchEngine']);

            if (false === $container->hasDefinition('repregid_api.search_engine')) {
                throw new \Exception("'".$config['searchEngine']."' search engine could not be found!");
            }
        }

        if ($config['postponedCommands']) {
            $container
                ->register(PostponedCommandListener::class)
                ->setAutowired(true)
                ->addTag('kernel.event_subscriber');
        }
    }
}
