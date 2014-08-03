<?php

namespace Tg\OkoaBundle\Util;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator\Constraints as Assert;
use ReflectionProperty;
use ReflectionClass;
use Exception;

/**
 * Annotation utility functions.
 */
class AnnotationUtil
{
    /**
	 * Retrieve an annotation for a Field
	 * @param	string	The classname of the class to retrieve the annotation for
	 * @param	string	The name of the annotation to retrieve
	 * @param	string	The name of the property to retrieve the annotation for, if any
	 * @return 	mixed	The annotation if found or null otherwise
	 */
    public static function getAnnotation($className, $annotationName, $propertyName = null)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }
        $reader = new AnnotationReader();
        if ($propertyName === null) {
            $reflectionClass = new ReflectionClass($className);
            $annotation = $reader->getClassAnnotation($reflectionClass, $annotationName);
        } else {
            $reflectionProperty = new ReflectionProperty($className, $propertyName);
            $annotation = $reader->getPropertyAnnotation($reflectionProperty, $annotationName);
        }

        return $annotation;
    }

    /**
	 * Get the options that are available for a property of a class
	 * Options are only available if the @Assert\Choice annotation is set
	 * @param string $className  	The name of the class
	 * @param string $propertyName  The name of the property
	 * @return []                   An array of available options
	 */
    public static function getOptionsForField($className, $propertyName)
    {
        $annotation = self::getAnnotation($className, 'Symfony\Component\Validator\Constraints\Choice', $propertyName);
        if (!$annotation) {
            throw new Exception(sprintf("Property '%s' does not have a Choice annotation.", $propertyName));
        }

        return array_combine($annotation->choices, $annotation->choices);
    }

    /**
	 * Get the types that a Subclass instances of a Superclass may have
	 * Only available if the @ORM\DiscriminatorMap annotation is available
	 * @param string $className  	The name of the class
	 * @return []                   An array of available types
	 */
    public static function getTypesForClass($className)
    {
        $discriminatorMapAnnotation = self::getAnnotation($className, 'Doctrine\ORM\Mapping\DiscriminatorMap');

        return $discriminatorMapAnnotation->value;
        // $keys = array_keys($discriminatorMapAnnotation->value);
        // return array_combine($keys, $keys);
    }

    /**
	 * Get the object type from a discriminator type for a class
	 * Only available if the @ORM\DiscriminatorMap annotation is available
	 * @param string $className  			The name of the class
	 * @param string $discriminatorType  	The type as set in the discriminator column
	 * @return string                  		The real object type
	 */
    public static function mapDiscriminatorTypeToObjectTypeForClass($className, $type)
    {
        $discriminatorMapAnnotation = self::getAnnotation($className, 'Doctrine\ORM\Mapping\DiscriminatorMap');
        foreach ($discriminatorMapAnnotation->value as $key => $value) {
            if ($key === $type) {
                return $value;
            }
        }
        throw new Exception(sprintf("Class '%s' does not have a subtype '%s'.", $className, $type));
    }
}
