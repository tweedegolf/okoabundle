<?php

namespace Tg\OkoaBundle\Util;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator\Constraints as Assert;
use ReflectionProperty;
use Exception;

/**
 * Annotation utility functions.
 */
class AnnotationUtil
{
	/**
	 * Get the options that are available for a property of a class
	 * Options are only available if the @Assert\Choice annotation is set
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
		return $annotation->choices;
	}
}