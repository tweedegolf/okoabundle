<?php

namespace Tg\OkoaBundle\Behavior;

use Doctrine\Common\Annotations\Annotation;

/**
 * Denotes that the discriminator column for an entity will be dynamically determined.
 * Determining the discriminator is a very resource intensive process, and should only
 * be used when caching is enabled.
 * @Annotation
 */
class DynamicDiscriminator extends Annotation
{

}
