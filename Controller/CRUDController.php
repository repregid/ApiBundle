<?php

namespace Repregid\ApiBundle\Controller;


use Doctrine\Common\Persistence\ObjectRepository;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Repregid\ApiBundle\Repository\FilterRepository;
use Repregid\ApiBundle\Service\DataFilter\DataFilter;
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
     * @return SearchEngineInterface
     */
    public function getSearchEngine(): ?SearchEngineInterface
    {
        return $this->searchEngine;
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
     * @return ObjectRepository
     */
    protected function getRepo(string $entity) : ObjectRepository
    {
        return $this->getDoctrine()->getManager()->getRepository($entity);
    }

    /**
     * @param string $type
     * @param string $method
     * @param array $groups
     * @return FormInterface
     */
    protected function form(string $type, string $method = 'POST', array $groups = []): FormInterface
    {
        $builder = $this->formFactory->createBuilder(
            $type, null, ['validation_groups' => $groups]
        );

        $builder->setMethod($method);

        return $builder->getForm();
    }

    /**
     * @param Request $request
     * @param string $entity
     * @param array $groups
     * @param null $id - ID объекта фильтрации (для вложенных роутов)
     * @param null $field - название поля фильтрации (для вложенных роутов)
     * @return View
     */
    public function listAction(Request $request, string $entity, array $groups, $id = null, $field = null) : View
    {
        $filter = DataFilter::createFromRequest($request);
        $repo   = $this->getRepo($entity);

        if(!$repo instanceof FilterRepository) {
            return $this->renderBadRequest('This Entity cannot be listed and filtered.');
        }

        if($id && $field) {
            $filter->addCondition($field, '=', $id);
        }

        $repo->setSearchEngine($this->searchEngine);
        $data = $repo->findByFilter($filter);

        return $this->renderResultProvider($data, $groups);
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