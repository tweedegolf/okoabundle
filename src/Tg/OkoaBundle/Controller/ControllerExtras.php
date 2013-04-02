<?php

namespace Tg\OkoaBundle\Controller;

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
                $this->service_cache[$serviceName] = $this->get($name);
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

    abstract public function redirect($url, $status = 302);

    abstract public function get($name);

    abstract public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH);

    abstract public function getRequest();
}
