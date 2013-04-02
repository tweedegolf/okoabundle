<?php

namespace Tg\OkoaBundle\Behavior;

use BadMethodCallException;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\Common\Util\ClassUtils;
use ReflectionClass;

/**
 * An object that can persist throughout requests by storing it in a database.
 */
abstract class Persistable extends PersistentObject
{
    /**
     * Remove an item from a relation collection and return it.
     * @param  string $field Fieldname of the relation
     * @param  array  $args  Arguments used for calling
     * @return mixed
     */
    private function remove($field, $args)
    {
        $getter = 'get' . ucfirst($field);
        $value = $this->$getter();
        if ($value instanceof Collection) {
            return $value->removeElement($args[0]);
        }
    }

    /**
     * Call for dynamic getters and setters, has and is-functions.
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $start = substr($method, 0, 2);

        // isProperty() methods
        if ($start === 'is') {
            $getter = 'get' . substr($method, 2);
            return (boolean)$this->$getter();

        // removeProperty() methods
        } else if ($start === 're' && substr($method, 0, 6) === 'remove') {
            $field = lcfirst(substr($method, 6));
            return $this->remove($field, $args);

        // hasProperty() methods
        } else if ($start === 'ha' && substr($method, 0, 3) === 'has') {
            $getter = 'get' . substr($method, 3);
            return (boolean)$this->$getter();

        // addProperty(), getProperty() and setProperty() methods
        } else {
            try {
                return parent::__call($method, $args);
            } catch (BadMethodCallException $e) {
                return parent::__call('get' . ucfirst($method), $args);
            }
        }
    }

    /**
     * Retrieve a property by calling the getter
     * @param  string $property
     * @return mixed
     */
    public function __get($property)
    {
        $getter = 'get' . ucfirst($property);
        return $this->$getter();
    }

    /**
     * Set a property by calling the getter
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        $setter = 'set' . ucfirst($property);
        $this->$setter($value);
    }

    public function __isset($property)
    {
        try {
            return $this->__get($property) !== null;
        } catch (BadMethodCallException $e) {
            return false;
        }
    }

    /**
     * Retrieve the name of the class
     * @return string
     */
    public static function classname()
    {
        return ClassUtils::getRealClass(get_called_class());
    }

    /**
     * Retrieve the repository according to the currently associated entity manager.
     * @return \Doctrine\ORM\EntityRepository
     */
    public static function repo()
    {
        return self::getObjectManager()->getRepository(static::classname());
    }

    /**
     * Retrieve the entity with the given identifier.
     * @param  int $id
     * @return Persistable|null
     */
    public static function find($id)
    {
        return static::repo()->find($id);
    }

    /**
     * Create a new querybuilder for the entity.
     * If no alias is given, the first letter from the classname is used.
     * @param  string $alias
     * @return \Doctrine\ORM\QueryBuilder
     */
    public static function qb($alias = null)
    {
        if ($alias === null) {
            $className = explode('\\', static::classname());
            $className = $className[count($className) - 1];
            $alias = strtolower($className[0]);
        }
        return static::repo()->createQueryBuilder($alias);
    }

    /**
     * Retrieve all entities matching the criteria, sorted accordingly, with a limit and offset.
     * If no criteria are given, all entitites will be returned, note that sorting, limit and offset
     * are ignored in that case.
     * @param  array $criteria
     * @param  array $orderBy
     * @param  int $limit
     * @param  int $offset
     * @return [Persistable]
     */
    public static function all(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $repo = static::repo();
        if ($criteria === null) {
            return $repo->findAll();
        } else {
            return $repo->findBy($criteria, $orderBy, $limit, $offset);
        }
    }

    /**
     * Retrieve one entity given the set of criteria, or null if none is found
     * @param  array  $criteria
     * @return Persistable|null
     */
    public static function one(array $criteria)
    {
        return static::repo()->findOneBy($criteria);
    }

    /**
     * Magic methods class::by[Name]() and class::oneBy[Name]
     * @param  string $name      Name of the called function
     * @param  array $arguments List of arguments
     * @return [Persistable]|Persistable|null
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, 0, 2) === 'by' || substr($name, 0, 5) === 'oneBy') {
            $repo = static::repo();
            $func = 'find' . ucfirst($name);
            return call_user_func_array(array($repo, $func), $arguments);
        } else {
            throw new \BadMethodCallException(sprintf("Unused static method %s", $name));
        }
    }
}
