<?php

namespace Repregid\ApiBundle\Controller;


use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\View\View;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Repregid\ApiBundle\DQLFunction\JsonbExistAnyFunction;
use Repregid\ApiBundle\DQLFunction\OperatorFunction;
use Repregid\ApiBundle\DQLFunction\JsonbArrayFunction;
use Repregid\ApiBundle\DQLFunction\ToJsonbFunction;
use Repregid\ApiBundle\Event\Events;
use Repregid\ApiBundle\Event\ExtraFilterFormEvent;
use Repregid\ApiBundle\Event\ListPostResultEvent;
use Repregid\ApiBundle\Service\DataFilter\CommonFilter;
use Repregid\ApiBundle\Service\DataFilter\Filter;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\CommonFilterType;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\DefaultFilterType;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\FilterType;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\PaginationType;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\ResultProviderType;
use Repregid\ApiBundle\Service\DataFilter\QueryBuilderUpdater;
use Repregid\ApiBundle\Service\Search\SearchEngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CRUDController
 * @package Repregid\ApiBundle\Controller
 */
class CRUDController extends APIController implements CRUDControllerInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var SearchEngineInterface
     */
    protected $searchEngine;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var FilterBuilderUpdaterInterface
     */
    protected $filterBuilderUpdater;

    /**
     * CRUDController constructor.
     * @param FormFactoryInterface $formFactory
     * @param EventDispatcherInterface $dispatcher
     * @param FilterBuilderUpdaterInterface $filterBuilderUpdater
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        EventDispatcherInterface $dispatcher,
        FilterBuilderUpdaterInterface $filterBuilderUpdater
    ) {
        $this->formFactory = $formFactory;
        $this->dispatcher = $dispatcher;
        $this->filterBuilderUpdater = $filterBuilderUpdater;
    }
    
    /**
     * @param SearchEngineInterface $searchEngine
     * @return $this
     */
    public function setSearchEngine(SearchEngineInterface $searchEngine)
    {
        $this->searchEngine = $searchEngine;

        return $this;
    }

    /**
     * @param string $entity
     * @return EntityRepository
     */
    protected function getRepo(string $entity): EntityRepository
    {
        return $this->getDoctrine()->getManager()->getRepository($entity);
    }

    /**
     * @param string $type
     * @param string $method
     * @param array $options
     * @param null $data
     * @return FormInterface
     */
    protected function form(
        string $type,
        string $method = 'POST',
        array $options = [],
        $data = null
    ): FormInterface
    {
        $builder = $this->formFactory->createBuilder(
            $type, $data, $options
        );

        $builder->setMethod($method);

        return $builder->getForm();
    }

    /**
     * @param Request $request
     * @param string $context
     * @param string $entity
     * @param array $groups
     * @param array $security - аттрибуты для isGranted
     * @param string $filterType - Тип формы фильтрации
     * @param string $filterMethod
     * @param null $id - ID объекта фильтрации (для вложенных роутов)
     * @param null $field - название поля фильтрации (для вложенных роутов)
     * @param string|null $softDeleteableFieldName
     * @param bool $fetchJoinCollection - для пагинации у сложных запросов
     * @return View
     */
    public function listAction(
        Request $request,
        string $context,
        string $entity,
        array $groups,
        array $security,
        string $filterType = DefaultFilterType::class,
        string $filterMethod = 'GET',
        $id = null,
        $field = null,
        $extraField = null,
        $softDeleteableFieldName = null,
        $fetchJoinCollection = false
    ): View
    {
        $repo           = $this->getRepo($entity);
        $filterBuilder  = $repo->createQueryBuilder('x');
        if (!empty($security)) {

            $this->denyAccessUnlessGrantedAny($security);
        }

        $filterEvent =  new ExtraFilterFormEvent($entity);
        $this->dispatcher->dispatch($filterEvent, Events::getExtraFilterEventName($context));

        $extraFilter = strval($filterEvent->getExtraFilter()) ?? '';


        if ($id && $extraField) {
            $sublistFilter = "$extraField=$id";
            $extraFilter = empty($extraFilter) ? $sublistFilter : $extraFilter.'&'.$sublistFilter;
        }


        $updater = $this->filterBuilderUpdater;

        $commonFilter = new CommonFilter();

        $form = $this->form(CommonFilterType::class, $filterMethod, [], $commonFilter);
        $form->get('extraFilter')->setData($extraFilter);
        $form->submit($request->query->all(), false);

        if ($form->isSubmitted() && !$form->isValid()) {
            return $this->renderFormError($form);
        }

        $extraFields = [
            $softDeleteableFieldName => false,
            $field => false
        ];

        //инициализация кастомных функций
        $em = $filterBuilder->getEntityManager();
        $em->getConfiguration()->addCustomNumericFunction(  OperatorFunction::name,        OperatorFunction::class      );
        $em->getConfiguration()->addCustomStringFunction(   JsonbExistAnyFunction::name,   JsonbExistAnyFunction::class );
        $em->getConfiguration()->addCustomStringFunction(   ToJsonbFunction::name,         ToJsonbFunction::class       );

        //Подня выше - чтобы в случае поиска в первую очередь сортировать по весу результата поиска
        QueryBuilderUpdater::addSearch($filterBuilder, $commonFilter, $this->searchEngine, $entity);

        //Из общей формы формы забираем предподготовленные формы для фильтрации
        //каждая форма порождает свою пачку join'ов, чтобы они не пересекались добавляем к алиасам индекс
        foreach ($commonFilter->getFilter() as $index => $filterQuery) {
            $filter = new Filter();
            $form = $this->form(FilterType::class, $filterMethod, ['filterType' => $filterType], $filter);
            //index в форме используется для создания разных join'ов и их алиасов на каждую форму
            $filterForm = [
                'filter' => $filterQuery,
                'index' => $index,
                'sort' => $commonFilter->getSort()
            ];

            foreach ($extraFields as $extraField => $meet) {
                if (array_key_exists($extraField, $filterQuery)) {
                    $extraFields[$extraField] = true;
                }
            }

            $form->submit($filterForm, false);
            if ($form->isSubmitted() && !$form->isValid()) {
                return $this->renderFormError($form);
            }
            //Забываем предыдущие join'ы, т.к. иначе FilterBuilderUpdater переиспользует существущие алиасы
            $updater->setParts([]);

            QueryBuilderUpdater::addFilter($filterBuilder, $form->get('filter'), $updater);
            QueryBuilderUpdater::addSorts($filterBuilder, $filter);
        }

        QueryBuilderUpdater::addPaginator($filterBuilder, $commonFilter);
        if ($id && $field && !$extraFields[$field]) {
            QueryBuilderUpdater::addExtraFilter($filterBuilder, $field, '=', $id);
        }

        if ($softDeleteableFieldName !== null && !$extraFields[$softDeleteableFieldName]) {
            QueryBuilderUpdater::addExtraFilter($filterBuilder, $softDeleteableFieldName, 'IS', 'NULL');
        }

        $result = QueryBuilderUpdater::createResultsProvider($filterBuilder, $commonFilter, $fetchJoinCollection);

        $postResultEvent = new ListPostResultEvent($entity, $result);
        $this->dispatcher->dispatch($postResultEvent, Events::EVENT_LIST_POST_RESULT);

        /* // Testing. Just add '/' at the start of this line.
            print($filterBuilder->getQuery()->getSQL());
            print_r($filterBuilder->getQuery()->getParameters()->toArray());
            die();
         /*/
         //*/

        return $this->renderResultProvider($postResultEvent->getResult(), $groups);
    }

    /**
     * @param Request $request
     * @param string $context
     * @param string $entity
     * @param array $groups
     * @param array $security
     * @param $id
     * @param string $idName
     * @return View
     */
    public function viewAction(
        Request $request,
        string $context,
        string $entity,
        array $groups,
        array $security,
        $id,
        string $idName = 'id'
    ): View
    {
        $repo = $this->getRepo($entity);
        $item = $repo->findOneBy([$idName => $id]);

        if (!empty($security)) {
            $this->denyAccessUnlessGrantedAny($security, $item);
        }

        return $item ? $this->renderOk($item, $groups) : $this->renderNotFound();
    }

    /**
     * @param Request $request
     * @param string $context
     * @param string $entity
     * @param array $groups
     * @param array $security
     * @param string $formType
     * @param string $formMethod
     * @return View
     */
    public function createAction(
        Request $request,
        string $context,
        string $entity,
        array $groups,
        array $security,
        string $formType,
        string $formMethod
    ): View
    {
        $item = new $entity();
        $form = $this->form($formType, $formMethod);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();
            if (!empty($security)) {
                $this->denyAccessUnlessGrantedAny($security, $item);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);
            $entityManager->flush();

            return $this->renderCreated($item, $groups);
        }

        return $this->renderFormError($form);
    }

    /**
     * @param Request $request
     * @param string $context
     * @param string $entity
     * @param array $groups
     * @param array $security
     * @param string $formType
     * @param string $formMethod
     * @param $id
     * @param string $idName
     * @return View
     */
    public function updateAction(
        Request $request,
        string $context,
        string $entity,
        array $groups,
        array $security,
        string $formType,
        string $formMethod,
        $id,
        string $idName = 'id'
    ): View
    {
        $repo = $this->getRepo($entity);
        $item = $repo->findOneBy([$idName => $id]);
        $form = $this->form($formType, $formMethod);

        if (!$item) {
            return $this->renderNotFound();
        }
        if (!empty($security)) {
            $this->denyAccessUnlessGrantedAny($security, $item);
        }

        $form->setData($item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);
            $entityManager->flush();

            return $this->renderOk($item, $groups);
        }

        return $this->renderFormError($form);
    }

    /**
     * @param Request $request
     * @param string $context
     * @param string $entity
     * @param array $security
     * @param $id
     * @param string $idName
     * @return View
     */
    public function deleteAction(
        Request $request,
        string $context,
        string $entity,
        array $security,
        $id,
        string $idName = 'id'
    ): View
    {
        $repo = $this->getRepo($entity);
        $item = $repo->findOneBy([$idName => $id]);

        if (!$item) {
            return $this->renderNotFound();
        }

        if (!empty($security)) {
            $this->denyAccessUnlessGrantedAny($security, $item);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($item);
        $entityManager->flush();

        return $this->renderResponse(['message' => 'item has been deleted']);
    }

    protected function denyAccessUnlessGrantedAny(array $attributes, $subject = null, string $message = 'Access Denied.'): void
    {
        foreach ($attributes as $attribute) {
            if ($this->isGranted($attribute, $subject)) {
                return;
            }
        }

        $exception = $this->createAccessDeniedException($message);
        $exception->setAttributes($attributes);
        $exception->setSubject($subject);

        throw $exception;
    }
}