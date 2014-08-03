<?php

namespace Tg\OkoaBundle\Twig;

use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Twig_Extension;
use Twig_SimpleFunction;
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
            new Twig_SimpleFunction('active_controller', [$this, 'activeController']),
            new Twig_SimpleFunction('active_action', [$this, 'activeAction']),
            new Twig_SimpleFunction('active_bundle', [$this, 'activeBundle']),
            new Twig_SimpleFunction('active_route', [$this, 'activeRoute']),
            new Twig_SimpleFunction('active', [$this, 'active']),
            new Twig_SimpleFunction(
                'is_a',
                function ($obj, $type) {
                    return is_a($obj, $type, false);
                }
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new Twig_SimpleTest(
                'active_controller',
                function ($controller, array $params = []) {
                    return $this->isActiveController($controller, $params);
                }
            ),
            new Twig_SimpleTest(
                'active_action',
                function ($action, array $params = []) {
                    return $this->isActiveAction($action, $params);
                }
            ),
            new Twig_SimpleTest(
                'active_bundle',
                function ($bundle, array $params = []) {
                    return $this->isActiveBundle($bundle, $params);
                }
            ),
            new Twig_SimpleTest(
                'active_route',
                function ($route, array $params = []) {
                    return $this->isActiveRoute($route, $params);
                }
            ),
            new Twig_SimpleTest(
                'active',
                function ($what, array $params = []) {
                    return $this->isActive($what, $params);
                }
            ),
            new Twig_SimpleTest('string', 'is_string'),
            new Twig_SimpleTest('array', 'is_array'),
            new Twig_SimpleTest('integer', 'is_integer'),
            new Twig_SimpleTest('boolean', 'is_bool'),
            new Twig_SimpleTest('object', 'is_object'),
            new Twig_SimpleTest('double', 'is_double'),
            new Twig_SimpleTest('float', 'is_float'),
            new Twig_SimpleTest('number', 'is_numeric'),
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
     * Returns the output if the given route is active. Returns false otherwise.
     * @param  string $what   Bundle, Controller class, or action method to be checked.
     * @param  mixed  $output Output that should be returned
     * @return mixed
     */
    public function active($what, $output = self::DEFAULT_ACTIVE_TEXT)
    {
        if ($this->isActive($what)) {
            return $output;
        } else {
            return false;
        }
    }

    /**
     * Check if the given route is active.
     * @param  string $what
     * @param  array  $params
     * @return bool
     */
    public function isActive($what, array $params = [])
    {
        $items = explode(':', $what);
        switch (count($items)) {
            case 1:
                return $this->isActiveBundle($what, $params);
            case 2:
                return $this->isActiveController($what, $params);
            case 3:
                return $this->isActiveAction($what, $params);
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
        if ($this->isActiveRoute($route)) {
            return $output;
        } else {
            return false;
        }
    }

    public function isActiveRoute($route, array $params = [])
    {
        $activeRoute = $this->container->get('request')->get('_route');
        $route = preg_quote($route, '@');
        $route = str_replace(['\*', '\+'], ['(.*?)', '(.+?)'], $route);
        if (preg_match('@^' . $route . '$@', $activeRoute) === 1) {
            return $this->hasParams($params);
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
        if ($this->isActiveController($controller)) {
            return $output;
        } else {
            return false;
        }
    }

    /**
     * Check if the given controller is currently active.
     * @param  string $controller
     * @param  array  $params
     * @return bool
     */
    public function isActiveController($controller, array $params = [])
    {
        list($bundle, $controllerName) = explode(':', $controller);
        $controller = $this->container->get('request')->get('_controller');
        $bundle = $this->container->get('kernel')->getBundle($bundle);
        $ns = $bundle->getNamespace() . '\Controller\\' . $controllerName . 'Controller::';
        if (strpos($controller, $ns) === 0) {
            return $this->hasParams($params);
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
        if ($this->isActiveAction($action)) {
            return $output;
        } else {
            return false;
        }
    }

    /**
     * Check if the given action is active.
     * @param  string $action
     * @param  array  $params
     * @return bool
     */
    public function isActiveAction($action, array $params = [])
    {
        list($bundle, $controllerName, $actionName) = explode(':', $action);
        $controller = $this->container->get('request')->get('_controller');
        $bundle = $this->container->get('kernel')->getBundle($bundle);
        $ns = $bundle->getNamespace() . '\Controller\\' . $controllerName . 'Controller::' . $actionName;
        if ($controller === $ns) {
            return $this->hasParams($params);
        } else {
            return false;
        }
    }

    /**
     * Returns the output if the given bundle is active. Returns false otherwise.
     * @param  string $bundle Name of the bundle that should be checked
     * @param  array  $params Parameters
     * @param  string $output Output that should be returned
     * @return mixed
     */
    public function activeBundle($bundle, $params = [], $output = self::DEFAULT_ACTIVE_TEXT)
    {
        if ($this->isActiveBundle($bundle, $params)) {
            return $output;
        } else {
            return false;
        }
    }

    /**
     * Check if the given bundle is active.
     * @param  string $bundle Name of the bundle that should be checked
     * @param  array  $params Parameters to be checked
     * @return bool
     */
    public function isActiveBundle($bundle, array $params = [])
    {
        $controller = $this->container->get('request')->get('_controller');
        $bundle = $this->container->get('kernel')->getBundle($bundle);
        $ns = $bundle->getNamespace() . '\Controller\\';
        if (strpos($controller, $ns) === 0) {
            return $this->hasParams($params);
        } else {
            return false;
        }
    }

    /**
     * Check if the list of parameters is in the current request.
     * @param  array $params
     * @return bool
     */
    public function hasParams(array $params = [])
    {
        $request = $this->container->get('request');
        foreach ($params as $param => $value) {
            $data = $request->attributes->get($param, $request->query->get($param));
            if ($data !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'okoa_extension';
    }
}
