<?php

namespace Tg\OkoaBundle\Controller;

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

    abstract public function redirect($url, $status = 302);

    abstract public function get($name);

    abstract public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH);

    abstract public function getRequest();
}
