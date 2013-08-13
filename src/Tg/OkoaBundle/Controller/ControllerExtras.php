<?php

namespace Tg\OkoaBundle\Controller;

use Doctrine\Common\Persistence\ObjectRepository;
use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tg\OkoaBundle\Util\TemplateBag;

trait ControllerExtras
{
    private $service_cache = [];

    public function __get($name)
    {
        if ($name === 'template') {
            if (!isset($this->service_cache[$name])) {
                $this->service_cache[$name] = new TemplateBag();
            }
        } else {
            if (!isset($this->service_cache[$name])) {
                $serviceName = preg_replace_callback('/[A-Z]/', function ($matches) {
                    return '.' . strtolower($matches[0]);
                }, $name);
                $this->service_cache[$name] = $this->get($serviceName);
            }
        }
        return $this->service_cache[$name];
    }

    public function url($route, $parameters = [], $addScheme = false)
    {
        $url = $this->generateUrl($route, $parameters, UrlGeneratorInterface::NETWORK_PATH);
        if ($addScheme) {
            $scheme = $this->getRequest()->getScheme();
            return $scheme . ':' . $url;
        }
        return $url;
    }

    public function relativeUrl($route, $parameters = [])
    {
        return $this->generateUrl($route, $parameters, UrlGeneratorInterface::RELATIVE_PATH);
    }

    public function path($route, $parameters = [])
    {
        return $this->generateUrl($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    public function redirectTo($route, $parameters = [], $status = 302)
    {
        return $this->redirect($this->path($route, $parameters), $status);
    }

    public function addFlash($type, $message)
    {
        $this->session->getFlashBag()->add($type, $message);
    }

    public function redirectBack($type, $message)
    {
        $this->addFlash($type, $message);
        $referer = $this->getRequest()->headers->get("referer");
        $baseUrl = $this->getRequest()->getSchemeAndHttpHost();
        if (strlen($referer) > strlen($baseUrl) && substr($referer, 0, strlen($baseUrl)) === $baseUrl) {
            return new RedirectResponse($referer);
        } else {
            throw new RuntimeException("Cannot redirect to route outside of current domain");
        }
    }

    public function getReferer()
    {
        $referer = $this->request->headers->get('referer');
        if ($referer) {
            $baseUrl = $this->getRequest()->getSchemeAndHttpHost();
            if (strpos($referer, $baseUrl) === 0) {
                $referer = substr($referer, strlen($baseUrl));
            }
        }
        return $referer;
    }

    public function storeReferer($route, $params, $store = '__referer')
    {
        $params['_route'] = $route;
        $this->session->set($store, $params);
    }

    public function updateStoredReferer($self = false, $store = '__referer')
    {
        $referer = $this->getReferer();
        if ($referer) {
            $route = $this->router->match($referer);
            if ($self === true || $this->request->get('_controller') !== $route['_controller']) {
                $this->storeReferer($route['_route'], $route);
            }
        }
    }

    public function redirectToStoredReferer($fallback, $fallbackParams = array(), $store = '__referer')
    {
        if ($this->session->has('referer')) {
            $redirectRoute = $this->session->get('referer');
            $redirectRoutePath = $redirectRoute['_route'];
            unset($redirectRoute['_route']);
            return $this->redirectTo($redirectRoutePath, $redirectRoute);
        } else {
            return $this->redirectTo($fallback, $fallbackParams);
        }
    }

    public function findOrNotFound($repo, $id = null, $message = 'Not Found')
    {
        if ($repo instanceof ObjectRepository) {
            $entity = $repo->find((int) $id);
        } else {
            $entity = $repo;
        }
        if ($entity === null) {
            throw $this->createNotFoundException($message);
        }
        return $entity;
    }

    abstract public function redirect($url, $status = 302);

    abstract public function get($name);

    abstract public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH);

    abstract public function getRequest();

    abstract public function createNotFoundException($message = 'Not Found', Exception $previous = null);
}
