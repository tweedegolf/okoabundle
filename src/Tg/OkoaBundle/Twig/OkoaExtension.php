<?php

namespace Tg\OkoaBundle\Twig;

use DateTime;
use Doctrine\Common\Inflector\Inflector;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig_Extension;
use Twig_Function_Function;
use Twig_SimpleFilter;
use Twig_SimpleTest;

/**
 * Generic extra twig helpers for okoa projects.
 */
class OkoaExtension extends Twig_Extension implements ContainerAwareInterface
{
    /**
     * Default output for route checking functions
     */
    const DEFAULT_ACTIVE_TEXT = 'active';

    /**
     * Container the extension is registered with
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('slugify', [$this, 'slugify']),
            new Twig_SimpleFilter('pluralize', [$this, 'pluralize']),
            new Twig_SimpleFilter('singularize', [$this, 'singularize']),
            new Twig_SimpleFilter('trans_date', [$this, 'transDate']),
            new Twig_SimpleFilter('trans_time', [$this, 'transTime']),
            new Twig_SimpleFilter('trans_datetime', [$this, 'transDateTime']),
        ];
    }

    /**
     * Transform any string into a slug.
     * Transliterate any non-ascii character to the equivalent
     * ascii character.
     *
     * @param string
     * @return string
     */
    public function slugify($string)
    {
        $string = preg_replace('~[^\\pL\d]+~u', '-', $string);
        $string = trim($string, '-');
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
        $string = strtolower($string);
        $string = preg_replace('~[^-\w]+~', '', $string);

        return $string;
    }

    /**
     * Pluralizes a noun given its singular form.
     * @param  string $string
     * @return string
     */
    public function pluralize($string)
    {
        return Inflector::pluralize($string);
    }

    /**
     * Creates the singular form given some plural of a noun.
     * @param  string $string
     * @return string
     */
    public function singularize($string)
    {
        return Inflector::singularize($string);
    }

    /**
     * Takes a DateTime object or unix timestamp and returns a localized formatted date and time.
     * @param  DateTime|integer $date
     * @param  string           $format
     * @return string
     */
    public function transDateTime($date, $format = '%d-%m-%Y %H:%M')
    {
        if ($date instanceof DateTime) {
            $date = $date->getTimestamp();
        }

        if (is_integer($date)) {
            return strftime($format, $date);
        } else {
            throw new LogicException("Invalid type given for formatting as a date, requires integer or DateTime");
        }
    }

    /**
     * Takes a DateTime object or unix timestamp and returns a localized formatted date.
     * @param  DateTime|integer $date
     * @param  string           $format
     * @return string
     */
    public function transDate($date, $format = '%d-%m-%Y')
    {
        return $this->transDateTime($date, $format);
    }

    /**
     * Takes a DateTime object or unix timestamp and returns a localized formatted time.
     * @param  DateTime|integer $date
     * @param  string           $format
     * @return string
     */
    public function transTime($date, $format = '%H:%M')
    {
        return $this->transDateTime($date, $format);
    }

    /**
     * Returns the output if the given route is active. Returns false otherwise.
     * @param  string $what   Bundle, Controller class, or action method to be checked.
     * @param  mixed  $output Output that should be returned
     * @return mixed
     */
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

    /**
     * Returns the output if the given route is active. Returns false otherwise.
     * @param  string $route  Named route
     * @param  mixed  $output Output that should be returned
     * @return mixed
     */
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

    /**
     * Returns the output if the given controller is active. Returns false otherwise.
     * @param  string $controller Class name of a controller
     * @param  mixed  $output     Output that should be returned
     * @return mixed
     */
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

    /**
     * Returns the output if the given action method is active. Returns false otherwise.
     * @param  string $action Class name and action method of the action that should be checked
     * @param  mixed  $output Output that should be returned
     * @return mixed
     */
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

    /**
     * Returns the output if the given bundle is active. Returns false otherwise.
     * @param  string $bundle Name of the bundle that should be checked
     * @param  mixed  $output Output that should be returned
     * @return mixed
     */
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'okoa_extension';
    }
}
