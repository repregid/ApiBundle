<?php

namespace Repregid\ApiBundle\Controller;


use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\View\View;
use Repregid\ApiBundle\Event\Events;
use Repregid\ApiBundle\Event\ExtraFilterFormEvent;
use Repregid\ApiBundle\Service\DataFilter\Filter;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\DefaultFilterType;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\FilterType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Repregid\ApiBundle\Repository\FilterRepository;
use Repregid\ApiBundle\Service\Search\SearchEngineInterface;

/**
 * Class CRUDController
 * @package Repregid\ApiBundle\Controller
 */
class CRUDController extends APIController
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
     * CRUDController constructor.
     *
     * @param FormFactoryInterface $formFactory
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(FormFactoryInterface $formFactory, EventDispatcherInterface $dispatcher)
    {
        $this->formFactory  = $formFactory;
        $this->dispatcher   = $dispatcher;
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
    protected function getRepo(string $entity) : EntityRepository
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
        $field = null
    ) : View
    {
        $repo           = $this->getRepo($entity);
        $filterBuilder  = $repo->createQueryBuilder('x');

        if(!$repo instanceof FilterRepository) {
            return $this->renderBadRequest('This Entity cannot be listed and filtered.');
        }

        foreach($security as $attribute) {
            $this->denyAccessUnlessGranted($attribute);
        }

        $filter = new Filter();
        $form = $this->form(FilterType::class, $filterMethod, ['filterType' => $filterType], $filter);

        $filterEvent =  new ExtraFilterFormEvent($entity);
        $this->dispatcher->dispatch(Events::getExtraFilterEventName($context), $filterEvent);

        $form->get('extraFilter')->setData(strval($filterEvent->getExtraFilter()) ?: '');

        $form->submit($request->query->all(), false);
        if($form->isSubmitted() && !$form->isValid()) {
            return $this->renderFormError($form);
        }

        $updater = $this->get('lexik_form_filter.query_builder_updater');
        $repo
            ->addFilter($filterBuilder, $form->get('filter'), $updater)
            ->addSorts($filterBuilder, $filter)
            ->addPaginator($filterBuilder, $filter)
            ->addSearch($filterBuilder, $filter, $this->searchEngine);

        if($id && $field) {
            $repo->addExtraFilter($filterBuilder, $field, '=', $id);
        }

        $result = $repo->createResultsProvider($filterBuilder, $filter);

        /* // Testing. Just add '/' at the start of this line.
            print($filterBuilder->getQuery()->getSQL());
            print_r($filterBuilder->getQuery()->getParameters()->toArray());
            die();
         /*/
         //*/

        return $this->renderResultProvider($result, $groups);
    }

    /**
     * @param Request $request
     * @param string $context
     * @param string $entity
     * @param array $groups
     * @param array $security
     * @param $id
     * @return View
     */
    public function viewAction(
        Request $request,
        string $context,
        string $entity,
        array $groups,
        array $security,
        $id
    ) : View
    {
        $repo = $this->getRepo($entity);
        $item = $repo->find($id);

        foreach($security as $attribute) {
            $this->denyAccessUnlessGranted($attribute, $item);
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
    ) : View
    {
        foreach($security as $attribute) {
            $this->denyAccessUnlessGranted($attribute);
        }

        $item = new $entity();
        $form = $this->form($formType, $formMethod);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();

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
        $id
    ) : View
    {
        $repo = $this->getRepo($entity);
        $item = $repo->find($id);
        $form = $this->form($formType, $formMethod);

        if(!$item) {
            return $this->renderNotFound();
        }

        foreach($security as $attribute) {
            $this->denyAccessUnlessGranted($attribute, $item);
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
     * @return View
     */
    public function deleteAction(
        Request $request,
        string $context,
        string $entity,
        array $security,
        $id
    ) : View
    {
        $repo = $this->getRepo($entity);
        $item = $repo->find($id);

        if(!$item) {
            return $this->renderNotFound();
        }

        foreach($security as $attribute) {
            $this->denyAccessUnlessGranted($attribute, $item);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($item);
        $entityManager->flush();

        return $this->renderResponse(['message' => 'item has been deleted']);
    }
}