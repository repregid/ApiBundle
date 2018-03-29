<?php

namespace Repregid\ApiBundle\Controller;


use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\View\View;
use Repregid\ApiBundle\Service\DataFilter\Filter;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\DefaultFilterType;
use Repregid\ApiBundle\Service\DataFilter\Form\Type\FilterType;
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
     * CRUDController constructor.
     *
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory  = $formFactory;
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
     * @param string $entity
     * @param array $groups
     * @param string $filterType - Тип формы фильтрации
     * @param string $filterMethod
     * @param null $id - ID объекта фильтрации (для вложенных роутов)
     * @param null $field - название поля фильтрации (для вложенных роутов)
     * @return View
     */
    public function listAction(
        Request $request,
        string $entity,
        array $groups,
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

        $filter = new Filter();
        $form = $this->form(FilterType::class, $filterMethod, ['filterType' => $filterType], $filter);

        $form->submit($request->query->all());
        if($form->isSubmitted() && !$form->isValid()) {
            return $this->renderFormError($form);
        }

        $updater = $this->get('lexik_form_filter.query_builder_updater');

        $result = $repo
            ->addFilter($filterBuilder, $form->get('filter'), $updater)
            ->addSorts($filterBuilder, $filter)
            ->addPaginator($filterBuilder, $filter)
            ->addSearch($filterBuilder, $filter, $this->searchEngine)
            ->createResultsProvider($filterBuilder, $filter);

        return $this->renderResultProvider($result, $groups);
    }

    /**
     * @param Request $request
     * @param string $entity
     * @param array $groups
     * @param $id
     * @return View
     */
    public function viewAction(Request $request, string $entity, array $groups, $id) : View
    {
        $repo = $this->getRepo($entity);
        $data = $repo->find($id);

        return $data ? $this->renderOk($data, $groups) : $this->renderNotFound();
    }

    /**
     * @param Request $request
     * @param string $entity
     * @param array $groups
     * @param string $formType
     * @param string $formMethod
     * @return View
     */
    public function createAction(
        Request $request,
        string $entity,
        array $groups,
        string $formType,
        string $formMethod
    ) : View
    {
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
     * @param string $entity
     * @param array $groups
     * @param string $formType
     * @param string $formMethod
     * @param $id
     * @return View
     */
    public function updateAction(
        Request $request,
        string $entity,
        array $groups,
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
     * @param string $entity
     * @param $id
     * @return View
     */
    public function deleteAction(Request $request, string $entity, $id) : View
    {
        $repo = $this->getRepo($entity);
        $item = $repo->find($id);

        if(!$item) {
            return $this->renderNotFound();
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($item);
        $entityManager->flush();

        return $this->renderResponse(['message' => 'item has been deleted']);
    }
}