<?php

namespace Tg\OkoaBundle\Behavior;

use BadMethodCallException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use LogicException;

/**
 * An object that can persist throughout requests by storing it in a database.
 */
abstract class Persistable extends PersistentObject
{
    /**
     * @param string $method
     * @param array  $args
     * @return void
     * @throws BadMethodCallException
     */
    public function __call($method, $args)
    {
        throw new BadMethodCallException(sprintf("No method '%s' in '%s'", $method, self::classname()));
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
     * @return ObjectRepository
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
     * If no alias is given, the first letter from the base classname is used.
     * @param  string $alias
     * @return QueryBuilder
     */
    public static function qb($alias = null)
    {
        if ($alias === null) {
            $className = explode('\\', static::classname());
            $className = $className[count($className) - 1];
            $alias = strtolower($className[0]);
        }
        $repo = static::repo();
        if ($repo instanceof EntityRepository) {
            return $repo->createQueryBuilder($alias);
        } else {
            throw new LogicException("Can only create query builder for ORM objects");
        }
    }

    /**
     * Retrieve all entities matching the criteria, sorted accordingly, with a limit and offset.
     * If no criteria are given, all entitites will be returned, note that sorting, limit and offset
     * are ignored in that case.
     * @param  array $criteria
     * @param  array $orderBy
     * @param  int $limit
     * @param  int $offset
     * @return Collection
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
     * @param  array  $arguments List of arguments
     * @return Collection|Persistable|null
     */
    public static function __callStatic($name, $arguments)
    {
        if (substr($name, 0, 2) === 'by' || substr($name, 0, 5) === 'oneBy') {
            $repo = static::repo();
            $func = 'find' . ucfirst($name);
            return call_user_func_array(array($repo, $func), $arguments);
        } else {
            throw new BadMethodCallException(
                sprintf("Invalid static method call '%s' in '%s'", $name, static::classname())
            );
        }
    }
}
