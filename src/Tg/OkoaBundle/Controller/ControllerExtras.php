<?php

namespace Tg\OkoaBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tg\OkoaBundle\Util\TemplateBag;

trait ControllerExtras
{
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
     * Retrieve the object manager with the given name
     * @param  string $name
     * @return ObjectManager
     */
    public function getManager($name = null)
    {
        return $this->container->get('doctrine')->getManager($name);
    }

    /**
     * Retrieve a repository from the default entity manager
     * @param  string $name Class or shortened name
     * @return ObjectRepository
     */
    public function getRepository($name)
    {
        return $this->getManager()->getRepository($name);
    }
}
