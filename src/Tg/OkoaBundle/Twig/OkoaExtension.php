<?php

namespace Tg\OkoaBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig_Extension;
use Twig_Function_Function;
use Twig_SimpleTest;

class OkoaExtension extends Twig_Extension implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return [
            'active_controller' => new Twig_Function_Function(array($this, 'activeController')),
            'active_action' => new Twig_Function_Function(array($this, 'activeAction')),
            'active_bundle' => new Twig_Function_Function(array($this, 'activeBundle')),
            'active_route' => new Twig_Function_Function(array($this, 'activeRoute')),
            'active' => new Twig_Function_Function(array($this, 'active')),
        ];
    }

    public function getTests()
    {
        return [
            'active_controller' => new Twig_SimpleTest('active_controller', function ($controller) {
                return $this->activeController($controller) !== false;
            }),
            'active_action' => new Twig_SimpleTest('active_action', function ($action) {
                return $this->activeAction($action) !== false;
            }),
            'active_bundle' => new Twig_SimpleTest('active_bundle', function ($bundle) {
                return $this->activeBundle($bundle) !== false;
            }),
            'active_route' => new Twig_SimpleTest('active_route', function ($route) {
                return $this->activeRoute($route) !== false;
            }),
            'active' => new Twig_SimpleTest('active', function ($what) {
                return $this->active($what) !== false;
            }),
        ];
    }

    public function active($what, $output = 'class="active"')
    {
        $items = explode(':', $what);
        switch (count($items)) {
            case 1:
                return $this->activeBundle($what, $output);
            case 2:
                return $this->activeController($what, $output);
            case 3:
                return $this->activeAction($what, $output);
            default:
                return false;
        }
    }

    public function activeRoute($route, $output = 'class="active"')
    {
        $activeRoute = $this->container->get('request')->get('_route');
        $route = preg_quote($route, '@');
        $route = str_replace(['\*', '\+'], ['(.*?)', '(.+?)'], $route);
        if (preg_match('@^' . $route . '$@', $activeRoute) === 1) {
            return $output;
        } else {
            return false;
        }
    }

    public function activeController($controller, $output = 'class="active"')
    {
        list($bundle, $controllerName) = explode(':', $controller);
        $controller = $this->container->get('request')->get('_controller');
        $bundle = $this->container->get('kernel')->getBundle($bundle);
        $ns = $bundle->getNamespace() . '\Controller\\' . $controllerName . 'Controller::';
        if (strpos($controller, $ns) === 0) {
            return $output;
        } else {
            return false;
        }
    }

    public function activeAction($action, $output = 'class="active"')
    {
        list($bundle, $controllerName, $actionName) = explode(':', $action);
        $controller = $this->container->get('request')->get('_controller');
        $bundle = $this->container->get('kernel')->getBundle($bundle);
        $ns = $bundle->getNamespace() . '\Controller\\' . $controllerName . 'Controller::' . $actionName;
        if ($controller === $ns) {
            return $output;
        } else {
            return false;
        }
    }

    public function activeBundle($bundle, $output = 'class="active"')
    {
        $controller = $this->container->get('request')->get('_controller');
        $bundle = $this->container->get('kernel')->getBundle($bundle);
        $ns = $bundle->getNamespace() . '\Controller\\';
        if (strpos($controller, $ns) === 0) {
            return $output;
        } else {
            return false;
        }
    }

    public function getName()
    {
        return 'okoa_extension';
    }
}
