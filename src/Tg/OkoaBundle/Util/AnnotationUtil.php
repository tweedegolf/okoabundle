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
	 * Get the options that are available for a property of a class
	 * Options are only available if the @Assert\Choice annotation is set
	 * @param string $className  	The name of the class
	 * @param string $propertyName  The name of the property
	 * @return []                   An array of available options
	 */
	public static function getOptionsForField($className, $propertyName)
	{
		if(is_object($className)) {
			$className = get_class($className);
		}
		$reflectionProperty = new ReflectionProperty($className, $propertyName);
		$reader = new AnnotationReader();
		$annotation = $reader->getPropertyAnnotation($reflectionProperty, 'Symfony\Component\Validator\Constraints\Choice');
		if(!$annotation) {
			throw new Exception(sprintf("Property '%s' does not have a Choice annotation.", $propertyName));
		}
		return array_combine($annotation->choices, $annotation->choices);
	}
	
	/**
	 * Get the types that a Subclass instances of a Superclass may have
	 * Only available if the @ORM\InheritanceType is SINGLE_TABLE and the @ORM\DiscriminatorMap
	 * annotation is available
	 * @param string $className  	The name of the class
	 * @return []                   An array of available types
	 */
	public static function getTypesForClass($className)
	{
		if(is_object($className)) {
			$className = get_class($className);
		}
		$reflectionClass = new ReflectionClass($className);
		$reader = new AnnotationReader();
		$inheritanceTypeAnnotation = $reader->getClassAnnotation($reflectionClass, 'Doctrine\ORM\Mapping\InheritanceType');
		$discriminatorMapAnnotation = $reader->getClassAnnotation($reflectionClass, 'Doctrine\ORM\Mapping\DiscriminatorMap');
		if(!$inheritanceTypeAnnotation || $inheritanceTypeAnnotation->value !== 'SINGLE_TABLE') {
			throw new Exception(sprintf("Class '%s' does not have single table inheritance.", $className));
		}
		$keys = array_keys($discriminatorMapAnnotation->value);
		return array_combine($keys, $keys);
	}
	
	/**
	 * Get the object type from a discriminator type for a class
	 * Only available if the @ORM\InheritanceType is SINGLE_TABLE and the @ORM\DiscriminatorMap
	 * annotation is available
	 * @param string $className  			The name of the class
	 * @param string $discriminatorType  	The type as set in the discriminator column
	 * @return string                  		The real object type
	 */
	public static function mapDiscriminatorTypeToObjectTypeForClass($className, $type)
	{
		if(is_object($className)) {
			$className = get_class($className);
		}
		$reflectionClass = new ReflectionClass($className);
		$reader = new AnnotationReader();
		$inheritanceTypeAnnotation = $reader->getClassAnnotation($reflectionClass, 'Doctrine\ORM\Mapping\InheritanceType');
		$discriminatorMapAnnotation = $reader->getClassAnnotation($reflectionClass, 'Doctrine\ORM\Mapping\DiscriminatorMap');
		if($inheritanceTypeAnnotation->value !== 'SINGLE_TABLE') {
			throw new Exception(sprintf("Class '%s' does not have single table inheritance.", $className));
		}
		foreach($discriminatorMapAnnotation->value as $key => $value) {
			if($key === $type) {
				return $value;
			}
		}
		throw new Exception(sprintf("Class '%s' does not have a subtype '%s'.", $className, $type));
	}
}