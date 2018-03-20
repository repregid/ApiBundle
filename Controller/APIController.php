<?php

namespace Repregid\ApiBundle\Controller;


use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Repregid\ApiBundle\Repository\ResultProvider;

/**
 * Class APIController
 * @package Repregid\ApiBundle\Controller
 */
class APIController extends FOSRestController
{
    const MESSAGE_NOT_FOUND         = 'Not found';
    const MESSAGE_BAD_REQUEST       = 'Bad request';
    const MESSAGE_INTERNAL_ERROR    = 'Internal error';
    const MESSAGE_TIMEOUT           = 'Timeout';
    const MESSAGE_FORBIDDEN         = 'Forbidden';
    const MESSAGE_UNAUTHORIZED      = 'Unauthorized';

    /**
     * @param $data
     * @param array $groups
     * @param int $code
     * @return View
     */
    protected function renderResponse($data, int $code = Response::HTTP_OK, array $groups = []) : View
    {
        $view = View::create()
            ->setStatusCode($code)
            ->setData($data);

        if(count($groups) > 0) {
            $view->getContext()->setGroups($groups);
        }

        return $view;
    }

    /**
     * @param $code
     * @param $message
     * @param array $params
     * @return View
     */
    protected function renderFail(
        int     $code       = Response::HTTP_INTERNAL_SERVER_ERROR,
        string  $message    = self::MESSAGE_INTERNAL_ERROR,
        array   $params     = []
    ) : View
    {
        return $this->renderResponse([
            'message' => $message,
            'params' => $params
        ], $code);
    }

    /**
     * @param string $message
     * @return View
     */
    protected function renderNotFound(string $message = self::MESSAGE_NOT_FOUND): View
    {
        return $this->renderFail(Response::HTTP_NOT_FOUND, $message);
    }

    /**
     * @param string $message
     * @return View
     */
    protected function renderBadRequest(string $message = self::MESSAGE_BAD_REQUEST) : View
    {
        return $this->renderFail(Response::HTTP_BAD_REQUEST, $message);
    }

    /**
     * @param string $message
     * @return View
     */
    protected function renderInternalError(string $message = self::MESSAGE_INTERNAL_ERROR) : View
    {
        return $this->renderFail(Response::HTTP_INTERNAL_SERVER_ERROR, $message);
    }

    /**
     * @param string $message
     * @return View
     */
    protected function renderTimeout(string $message = self::MESSAGE_TIMEOUT) : View
    {
        return $this->renderFail(Response::HTTP_REQUEST_TIMEOUT, $message);
    }

    /**
     * @param string $message
     * @return View
     */
    protected function renderForbidden(string $message = self::MESSAGE_FORBIDDEN) : View
    {
        return $this->renderFail(Response::HTTP_FORBIDDEN, $message);
    }

    /**
     * @param string $message
     * @return View
     */
    protected function renderUnAuthorized(string $message = self::MESSAGE_UNAUTHORIZED) : View
    {
        return $this->renderFail(Response::HTTP_UNAUTHORIZED, $message);
    }

    /**
     * @param $data
     * @param array $groups
     * @return View
     */
    protected function renderCreated($data, $groups = []) : View
    {
        return $this->renderResponse($data, Response::HTTP_CREATED, $groups);
    }

    /**
     * @param $data
     * @param array $groups
     * @return View
     */
    protected function renderOk($data, $groups = []) : View
    {
        return $this->renderResponse($data, Response::HTTP_OK, $groups);
    }

    /**
     * @return View
     */
    protected function renderNoContent() : View
    {
        return View::create()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * @param $data
     * @param array $groups
     * @return View
     */
    protected function renderFormError($data, array $groups = []) : View
    {
        return $this->renderResponse($data, Response::HTTP_BAD_REQUEST, $groups);
    }

    /**
     * @param ResultProvider $resultProvider
     * @param array $groups
     * @return View
     */
    public function renderResultProvider(ResultProvider $resultProvider, array $groups = [])
    {
        $groups[] = "result_provider";

        return $this->renderResponse($resultProvider, Response::HTTP_OK, array_unique($groups));
    }
}