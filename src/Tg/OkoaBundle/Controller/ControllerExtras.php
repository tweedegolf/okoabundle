<?php

namespace Tg\OkoaBundle\Controller;

use Doctrine\Common\Persistence\ObjectRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tg\OkoaBundle\Util\TemplateBag;

trait ControllerExtras
{
    /**
     * A list of services that were cached.
     * @var array
     */
    private $service_cache = [];

    /**
     * Retrieve an object from the service container
     * @param string $name
     * @return object
     */
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
                $this->service_cache[$name] = $this->container->get($serviceName);
            }
        }
        return $this->service_cache[$name];
    }

    /**
     * Generate a full url for a route.
     * @param  string $route
     * @param  array  $parameters
     * @param  bool   $addScheme
     * @return string
     */
    public function url($route, $parameters = [], $addScheme = false)
    {
        $url = $this->container->get('router')->generate($route, $parameters, UrlGeneratorInterface::NETWORK_PATH);
        if ($addScheme) {
            $scheme = $this->container->get('request')->getScheme();
            return $scheme . ':' . $url;
        }
        return $url;
    }

    /**
     * Generate a relative url for a route (relative to the current url).
     * @param  string $route
     * @param  array  $parameters
     * @return string
     */
    public function relativeUrl($route, $parameters = [])
    {
        return $this->container->get('router')->generate($route, $parameters, UrlGeneratorInterface::RELATIVE_PATH);
    }

    /**
     * Generate the absolute path for a route.
     * @param  string $route
     * @param  array  $parameters
     * @return string
     */
    public function path($route, $parameters = [])
    {
        return $this->container->get('router')->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Redirect to another route.
     * @param  string $route
     * @param  array  $parameters
     * @param  int    $status
     * @return RedirectResponse
     */
    public function redirectTo($route, $parameters = [], $status = 302)
    {
        return new RedirectResponse($this->path($route, $parameters), $status);
    }

    /**
     * Add a flash message to the session.
     * @param string $type
     * @param string $message
     * @return $this
     */
    public function addFlash($type, $message)
    {
        $this->container->get('session')->getFlashBag()->add($type, $message);
        return $this;
    }

    /**
     * Add a flash message and redirect back to the referer.
     * @param  string $type
     * @param  string $message
     * @return RedirectResponse
     * @throws \RuntimeException
     */
    public function redirectBack($type, $message)
    {
        $this->addFlash($type, $message);
        $referer = $this->container->get('request')->headers->get("referer");
        $baseUrl = $this->container->get('request')->getSchemeAndHttpHost();
        if (strlen($referer) > strlen($baseUrl) && substr($referer, 0, strlen($baseUrl)) === $baseUrl) {
            return new RedirectResponse($referer);
        } else {
            throw new RuntimeException("Cannot redirect to route outside of current domain");
        }
    }

    /**
     * Retrieve the referer url (in the form of an absolute path) from the request.
     * @return string
     */
    public function getReferer()
    {
        $referer = $this->container->get('request')->headers->get('referer');
        if ($referer) {
            $baseUrl = $this->container->get('request')->getSchemeAndHttpHost();
            if (strpos($referer, $baseUrl) === 0) {
                $referer = substr($referer, strlen($baseUrl));
            }
        }
        return $referer;
    }

    /**
     * Save a new location for the referer to the session
     * @param string $route
     * @param array  $params
     * @param string $store
     */
    public function storeReferer($route, $params, $store = '__referer')
    {
        $params['_route'] = $route;
        $this->container->get('session')->set($store, $params);
    }

    /**
     * Update the stored referer depending on some conditions
     * @param bool   $self  If true, only update when the referer is not the current page.
     * @param string $store Name of the session key to store the data in.
     * @return bool
     */
    public function updateStoredReferer($self = false, $store = '__referer')
    {
        $referer = $this->getReferer();
        if ($referer) {
            $route = $this->container->get('router')->match($referer);
            if ($self === true || $this->container->get('request')->get('_controller') !== $route['_controller']) {
                $this->storeReferer($route['_route'], $route);
                return true;
            }
        }
        return false;
    }

    /**
     * Redirect to the stored referer or to the given parameters if none is found.
     * @param  string $fallback       The fallback route name
     * @param  array  $fallbackParams The fallback route parameters
     * @param  string $store          Location where the referer data is stored
     * @return RedirectResponse
     */
    public function redirectToStoredReferer($fallback, $fallbackParams = array(), $store = '__referer')
    {
        if ($this->container->get('session')->has('referer')) {
            $redirectRoute = $this->container->get('session')->get('referer');
            $redirectRoutePath = $redirectRoute['_route'];
            unset($redirectRoute['_route']);
            return $this->redirectTo($redirectRoutePath, $redirectRoute);
        } else {
            return $this->redirectTo($fallback, $fallbackParams);
        }
    }

    /**
     * Try to retrieve some object from the repository or fail with a not found exception.
     * @param  mixed  $repo
     * @param  mixed  $id
     * @param  string $message
     * @return object
     * @throws NotFoundHttpException
     */
    public function findOrNotFound($repo, $id = null, $message = 'Not Found')
    {
        if ($repo instanceof ObjectRepository) {
            $entity = $repo->find((int) $id);
        } else {
            $entity = $repo;
        }
        if ($entity === null) {
            throw new NotFoundHttpException($message);
        }
        return $entity;
    }
}
