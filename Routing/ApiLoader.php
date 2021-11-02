<?php

namespace Repregid\ApiBundle\Routing;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\DefaultFilterType;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Repregid\ApiBundle\Action\Action;
use Repregid\ApiBundle\Annotation\APIContext;
use Repregid\ApiBundle\Annotation\APIEntity;
use Repregid\ApiBundle\Annotation\APISubList;
use Repregid\ApiBundle\DependencyInjection\Configurator;

/**
 * Class ApiLoader
 * @package Repregid\ApiBundle\Routing
 */
final class ApiLoader extends Loader
{
    const ROUTE_PREFIX = 'repregid_api';

    /**
     * @var Configurator
     */
    protected $configurator;

    /**
     * @var Inflector
     */
    protected $inflector;

    /**
     * @var Route[]
     */
    protected $importedRoutes = [];

    /**
     * @var array
     */
    protected $includedFiles = [];

    /**
     * ApiLoader constructor.
     * @param Configurator $configurator
     */
    public function __construct(Configurator $configurator)
    {
        $this->configurator = $configurator;
        $this->inflector = InflectorFactory::create()->build();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'repregid_api' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        $this->includeFiles();
        $this->importRoutes();

        $annotations    = $this->readAnnotations();
        $resultRoutes   = new RouteCollection();

        $this->loadMainRoutes($annotations, $resultRoutes);
        $this->loadSubRoutes($annotations, $resultRoutes);

        return $resultRoutes;
    }

    /**
     * @param APIEntity[] $annotations
     * @param RouteCollection $resultRoutes
     * @return void
     */
    private function loadMainRoutes($annotations, RouteCollection $resultRoutes)
    {
        /**
         * 1. Read annotations from entity and build routes
         */
        foreach ($annotations as $className => $annotation) {

            $shortName  = self::getShortName($className);
            $contexts   = $annotation->contexts;

            foreach ($contexts as $key => $context) {

                /**
                 * Let's using simple
                 */
                if (is_string($context)) {
                    $key = $context;
                    $context = new APIContext();
                }

                $contextConfig  = $this->getContextConfig($key);
                $bindings       = $context->getBindings();

                /**
                 * Check all bindings
                 */
                foreach ($bindings as $binding) {
                    $this->getContextConfig($binding);
                }

                $contextRoutes = new RouteCollection();

                $uri = $context->getUri() ?? lcfirst($this->inflector->pluralize($shortName));
                $url =
                    '/'.trim($contextConfig['url'], '/').
                    '/'.trim($uri, '/');

                /**
                 * If "default" action exists, adding all actions from config.
                 */
                $actions = $context->getActions();
                $actions = $this->replaceDefault($actions, $contextConfig['actions']);

                $contextTypes       = $context->getTypes();
                $contextGroups      = $context->getSerializationGroups();
                $contextSecurity    = $context->getSecurity();
                if (empty($context->getSecurity())) {
                    $contextSecurity = $contextConfig['security'];
                }

                $actionNames = array_unique($actions);
                $actions = [];

                foreach ($actionNames as $actionName) {
                    $actions[$actionName] = [
                        'type'      => $contextTypes[$actionName] ?? $contextTypes['all'] ?? '' ,
                        'groups'    => $contextGroups[$actionName] ?? $contextGroups['all'] ?? [],
                        'security'  => $contextSecurity[$actionName] ?? $contextSecurity['all'] ?? []
                    ];
                }

                foreach ($actions as $actionName => $actionParams) {

                    $filterType = $annotation->filterType;
                    $formType   = $actionParams['type'] ?: $annotation->formType;
                    $action     = $this->getAction($actionName, $formType, $filterType);

                    $groupSuffix    = $action->getDefault('groupSuffix');
                    $defaultGroups  = $groupSuffix ? [$key.'_'.$groupSuffix] : [];

                    foreach ($bindings as $binding) {
                        $defaultGroups[] = $binding.'_'.$groupSuffix;
                    }

                    $groups = $actionParams['groups'] ? $this->replaceDefault($actionParams['groups'], $defaultGroups) : $defaultGroups;

                    $action->addDefaults([
                        'context'       => $key,
                        'entity'        => $className,
                        'groups'        => $groups,
                        'security'      => is_string($actionParams['security']) ? [$actionParams['security']] : $actionParams['security'],
                        'searchFields'  => $context->getSearchFields(),
                        'allowUnlimited'=> $annotation->allowUnlimited,
                        'infinitePages' => $annotation->infinitePages
                    ]);

                    if ($action->hasRequirement('id')) {
                        $action->setRequirement('id', $context->getIdRequirement());
                    }

                    if ($action->hasDefault('idName')) {
                        $action->setDefault('idName', $context->getIdName());
                    }

                    if ($action->getDefault('_controller') === 'repregid_api.controller.crud:listAction'
                        || $action->getDefault('_controller') === 'repregid_api.controller.crud::listAction') {

                        $listBehavior = $annotation->listWithSoftDeleteable !== null
                            ? $annotation->listWithSoftDeleteable
                            : $this->configurator->isListWithSoftDeleteable();

                        if (!$listBehavior) {
                            $action->setDefault('softDeleteableFieldName', $annotation->softDeleteableFieldName);
                        }
                    }

                    $contextRoutes->add($this->getRouteName($key, $shortName, $actionName), $action);
                }
                $contextRoutes->addPrefix($url);
                foreach ($contextRoutes as $contextRoute) {
                    $contextRoute->setPath(rtrim($contextRoute->getPath(), '/'));
                }
                $resultRoutes->addCollection($contextRoutes);
            }
        }
    }

    /**
     * @param APIEntity[] $annotations
     * @param RouteCollection $resultRoutes
     * @return void
     */
    private function loadSubRoutes($annotations, RouteCollection $resultRoutes)
    {
        foreach ($annotations as $className => $annotation) {

            $subLists   = $annotation->subLists;

            foreach ($subLists as $subList) {

                $listRoute = $this->getRouteName($subList->getListContext(), $subList->getListClass(), Action::ACTION_LIST);
                $viewRoute = $this->getRouteName($subList->getViewContext(), $subList->getViewClass(), Action::ACTION_VIEW);

                $list = $resultRoutes->get($listRoute);
                $view = $resultRoutes->get($viewRoute);

                if(!$view || !$list) {
                    continue;
                }

                $list = clone $list;

                $name   = $viewRoute.'_'.$listRoute;
                $uri    = self::getShortName($subList->getListClass());
                $list
                    ->setPath($view->getPath().'/'.lcfirst($this->inflector->pluralize($uri)))
                    ->setDefault('field', $subList->getField())
                    ->setDefault('extraField', $subList->getExtraField())
                    ->setDefault('subViewClass', $subList->getViewClass())
                ;

                if ($subList->getIdRequirement('id')) {
                    $list->setRequirement('id', $subList->getIdRequirement());
                }

                if ($subList->getIdName('idName')) {
                    $list->setDefault('idName', $subList->getIdName());
                }

                $resultRoutes->add($name, $list);
            }
        }
    }

    /**
     * @param $class
     * @return mixed
     */
    private static function getShortName($class)
    {
        $parts = explode('\\', $class);

        return end($parts);
    }

    /**
     * @param $context
     * @param $className
     * @param $action
     * @return string
     */
    private function getRouteName($context, $className, $action)
    {
        return implode('_', [
            self::ROUTE_PREFIX,
            $context,
            $this->inflector->tableize(self::getShortName($className)),
            $action
        ]);
    }

    /**
     * Load all files from sourcePaths
     *
     * @return void
     */
    private function includeFiles()
    {
        $configuratorPaths  = $this->configurator->getEntityPaths();

        /**
         * 1. Load all files from sourcePaths
         */
        foreach ($configuratorPaths as $path) {

            $finder = new Finder();
            $finder->files()->in($path);

            foreach ($finder as $file) {
                $realPath = $file->getRealPath();

                require_once $realPath;

                $this->includedFiles[$realPath] = true;
            }
        }
    }

    /**
     * Read all action configs and load routes
     *
     * @return void
     */
    private function importRoutes()
    {
        $configuratorActions    = $this->configurator->getActionPaths();
        $configuratorActions[]  = $this->configurator->getDefaultActions();

        foreach($configuratorActions as $actionsPath) {
            foreach($this->import($actionsPath, 'yaml') as $key => $route) {
                $this->importedRoutes[$key] = $route;
            }
        }
    }

    /**
     * Read annotations from entities
     *
     * @return APIEntity []
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    private function readAnnotations()
    {
        $result = [];

        foreach (get_declared_classes() as $className) {
            $reader = new AnnotationReader();
            $reflectionClass = new \ReflectionClass($className);

            if (!isset($this->includedFiles[$reflectionClass->getFileName()])) {
                continue;
            }

            /**
             * @var $annotation APIEntity
             */
            $annotation = null;
            if (PHP_VERSION_ID >= 80000) {
                $attributes = $reflectionClass->getAttributes(APIEntity::class);
                if (!empty($attributes)) {
                    $annotation = $attributes[0]->newInstance();
                }
            }
            if ($annotation === null) {
                $annotation = $reader->getClassAnnotation($reflectionClass, APIEntity::class);
            }

            if ($annotation) {

                $softDeletable  = $this->getSoftDeleteable($reflectionClass, $reader);
                if ($softDeletable) {
                    $annotation->softDeleteableFieldName = $softDeletable->fieldName;
                }
                if ($reflectionClass->implementsInterface('Knp\DoctrineBehaviors\Contract\Entity\SoftDeletableInterface')) {
                    $annotation->softDeleteableFieldName = 'deletedAt';
                }

                $result[$className] = $annotation;

                if (PHP_VERSION_ID >= 80000) {
                    foreach ($reflectionClass->getAttributes(APIContext::class) as $attribute) {
                        /** @var APIContext $context */
                        $context = $attribute->newInstance();
                        $annotation->contexts[$context->name] = $context;
                    }
                }

                foreach($reflectionClass->getProperties() as $reflectionProperty) {
                    $subLists = $reader->getPropertyAnnotations($reflectionProperty);

                    if (PHP_VERSION_ID >= 80000) {
                        foreach ($reflectionProperty->getAttributes(APISubList::class) as $attribute) {
                            $subLists[] = $attribute->newInstance();
                        }
                    }

                    foreach ($subLists as $subList) {
                        if ($subList instanceof APISubList) {
                            $rel =
                                $reader->getPropertyAnnotation($reflectionProperty, 'Doctrine\\ORM\\Mapping\\ManyToOne') ?:
                                $reader->getPropertyAnnotation($reflectionProperty, 'Doctrine\\ORM\\Mapping\\OneToMany') ?:
                                $reader->getPropertyAnnotation($reflectionProperty, 'Doctrine\\ORM\\Mapping\\ManyToMany')
                            ;
                            if (PHP_VERSION_ID >= 80000 && !$rel) {
                                $attrs = $reflectionProperty->getAttributes(ManyToOne::class);
                                if (empty($attrs)) {
                                    $attrs = $reflectionProperty->getAttributes(OneToMany::class);
                                }
                                if (empty($attrs)) {
                                    $attrs = $reflectionProperty->getAttributes(ManyToMany::class);
                                }
                                if (!empty($attrs)) {
                                    $rel = $attrs[0]->newInstance();
                                }
                            }

                            if(!$rel) {
                                continue;
                            }

                            $target = (FALSE === strrpos($rel->targetEntity, '\\'))     ?
                                $reflectionClass->getNamespaceName().'\\'.$rel->targetEntity    :
                                $rel->targetEntity;

                            if($rel instanceof ManyToMany) {
                                $subList->setViewClass($subList->getSide() === APISubList::SIDE_VIEW ? $target : $className);
                                $subList->setListClass( $subList->getSide() === APISubList::SIDE_LIST ? $target : $className);
                            } else {
                                $subList->setViewClass($rel instanceof ManyToOne ? $target : $className);
                                $subList->setListClass( $rel instanceof OneToMany ? $target : $className);
                            }

                            $annotation->subLists[] = $subList;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $result
     * @param array $replacement
     * @param bool $replaceIfEmpty
     * @param string $key
     * @return array
     */
    private function replaceDefault(
        array $result,
        array $replacement,
        bool $replaceIfEmpty = true,
        string $key = 'default'
    ) {
        if (FALSE !== ($index = array_search($key, $result))) {
            unset($result[$index]);
            $result = array_merge($result, $replacement);
        }

        if(!$result && $replaceIfEmpty) {
            $result = $replacement;
        }

        return $result;
    }

    /**
     * @param $key
     * @param $formType
     * @param $filterType
     * @return Route
     */
    private function getAction($key, $formType, $filterType): Route
    {
        if(!isset($this->importedRoutes[$key])) {
            throw new \InvalidArgumentException('API Action "'.$key.'" not found.');
        }

        $action = clone $this->importedRoutes[$key];
        $actionType     = $action->getDefault('formType');
        $actionFilter   = $action->getDefault('filterType');

        if($actionType === '*') {
            if(!$formType) {
                throw new \InvalidArgumentException('FormType must be specified for action "'.$key.'"');
            }

            $action->addDefaults(['formType' => $formType]);
        }

        if($actionFilter === '*') {
            $action->addDefaults(['filterType' => $filterType ?: DefaultFilterType::class]);
        }

        return $action;
    }

    /**
     * @param $key
     * @return array
     */
    private function getContextConfig($key)
    {
        $configuratorContexts = $this->configurator->getContexts();

        if(!isset($configuratorContexts[$key])) {
            throw new \InvalidArgumentException('API Context "'.$key.'" not found.');
        }

        return $configuratorContexts[$key];
    }

    /**
     * Поиск SoftDeleteable в родителях
     * @param $reflectionClass
     * @param $reader
     * @return mixed
     * @throws \ReflectionException
     */
    private function getSoftDeleteable($reflectionClass, $reader){
        $softDeleteable = $reader->getClassAnnotation($reflectionClass, 'Gedmo\\Mapping\\Annotation\\SoftDeleteable');

        if($softDeleteable){
            return $softDeleteable;
        }

        $parentClass = get_parent_class($reflectionClass->getName());

        if($parentClass){
            $parentReflectionClass = new \ReflectionClass($parentClass);
            $softDeleteable = $this->getSoftDeleteable($parentReflectionClass, $reader);
        }

        return $softDeleteable;
    }
}
