<?php

namespace Tg\OkoaBundle\Util\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class FormFieldLabel
{
	public $label;
	public $helptext;
}