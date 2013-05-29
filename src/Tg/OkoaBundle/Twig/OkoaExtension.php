<?php

namespace Tg\OkoaBundle\Twig;

use DateTime;
use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig_Extension;
use Twig_Function_Function;
use Twig_SimpleFilter;
use Twig_SimpleTest;

class OkoaExtension extends Twig_Extension implements ContainerAwareInterface
{
    const DEFAULT_ACTIVE_TEXT = 'active';

    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return [
            'active_controller' => new Twig_Function_Function([$this, 'activeController']),
            'active_action' => new Twig_Function_Function([$this, 'activeAction']),
            'active_bundle' => new Twig_Function_Function([$this, 'activeBundle']),
            'active_route' => new Twig_Function_Function([$this, 'activeRoute']),
            'active' => new Twig_Function_Function([$this, 'active']),
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
            'string' => new Twig_SimpleTest('string', 'is_string'),
            'array' => new Twig_SimpleTest('array', 'is_array'),
            'integer' => new Twig_SimpleTest('integer', 'is_integer'),
            'boolean' => new Twig_SimpleTest('boolean', 'is_bool'),
            'object' => new Twig_SimpleTest('object', 'is_object'),
            'double' => new Twig_SimpleTest('double', 'is_double'),
            'float' => new Twig_SimpleTest('float', 'is_float'),
            'number' => new Twig_SimpleTest('number', 'is_numeric'),
        ];
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('pluralize', [$this, 'pluralize']),
            new Twig_SimpleFilter('singularize', [$this, 'singularize']),
            new Twig_SimpleFilter('trans_date', [$this, 'transDate']),
            new Twig_SimpleFilter('trans_time', [$this, 'transTime']),
            new Twig_SimpleFilter('trans_datetime', [$this, 'transDateTime']),
        ];
    }

    public function pluralize($string)
    {
        return Inflector::pluralize($string);
    }

    public function singularize($string)
    {
        return Inflector::singularize($string);
    }

    public function transDateTime(DateTime $date, $format = '%d-%m-%Y %H:%M')
    {
        return strftime($format, $date->getTimestamp());
    }

    public function transDate(DateTime $date, $format = '%d-%m-%Y')
    {
        return strftime($format, $date->getTimestamp());
    }

    public function transTime(DateTime $date, $format = '%H:%M')
    {
        return strftime($format, $date->getTimestamp());
    }

    public function active($what, $output = self::DEFAULT_ACTIVE_TEXT)
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

    public function activeRoute($route, $output = self::DEFAULT_ACTIVE_TEXT)
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

    public function activeController($controller, $output = self::DEFAULT_ACTIVE_TEXT)
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

    public function activeAction($action, $output = self::DEFAULT_ACTIVE_TEXT)
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

    public function activeBundle($bundle, $output = self::DEFAULT_ACTIVE_TEXT)
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
