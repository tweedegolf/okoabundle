<?php

namespace Tg\OkoaBundle\Util\Traits;

use Tg\OkoaBundle\Util\AnnotationUtil;

trait FormFieldFactory
{
	
	/**
	 * Add a form field for a property to a Form
	 * @param	FormMapper	The FormMapper class of the Form to add a field to
	 * @param	string		The name of the property to add the field for
	 * @param	[]			An array of options. Currently supported are 'type', 'choices' and 'label'.
	 */
	public function addFieldForProperty($formMapper, $property, $options = array())
	{
		$formmapperOptionsArray = array();
		$formmapperType = array_key_exists('type', $options) ? $options['type'] : null;
		
		$this->addChoiceFieldOptions($formMapper, $property, $options, $formmapperOptionsArray);
		$this->addLabelOptions($formMapper, $property, $options, $formmapperOptionsArray);
		
		$formMapper->add($property, $formmapperType, $formmapperOptionsArray);
		return $this;
	}
	
	/**
	 * Add a choice field for a property to a Form
	 * If an array of possible choices is supplied, it will be used. Otherwise, the choices
	 * as defined by a Symfony\Component\Validator\Constraints\Choice annotation are used, if available.
	 */
	private function addChoiceFieldOptions($formMapper, $property, $options, &$optionsArray)
	{
		if(array_key_exists('type', $options) && $options['type'] === 'choice') {
			if(array_key_exists('choices', $options) && count($options['choices'] > 0)) {
				$optionsArray['choices'] = $options['choices'];
			} else {
				$optionsArray['choices'] = AnnotationUtil::getOptionsForField($formMapper->getAdmin()->getClass(), $property);
			}
		}
	}
	
	/**
	 * Add a label for a property to a Form
	 * If a label is supplied, it will be used. Otherwise, the Tg\OkoaBundle\Util\Annotations\FormFieldLabel
	 * annotation is used, if available. The fallback is the name of the property.
	 */
	private function addLabelOptions($formMapper, $property, $options, &$optionsArray)
	{
		if(array_key_exists('label', $options)) {
			$optionsArray['label'] = $options['label'];
		} else {
			$annotation = AnnotationUtil::getAnnotation($formMapper->getAdmin()->getClass(), 'Tg\OkoaBundle\Util\Annotations\FormFieldLabel', $property);
			if($annotation !== null && $annotation->label !== null) {
				$optionsArray['label'] = $annotation->label;
			}
		}
	}
}