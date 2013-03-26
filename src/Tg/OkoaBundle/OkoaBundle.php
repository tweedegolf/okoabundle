<?php

namespace Tg\OkoaBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Doctrine\Common\Persistence\PersistentObject;
use ReflectionClass;

/**
 * Okoa bundle
 */
class OkoaBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $em = $this->container->get('doctrine')->getManager();
        $reflector = new ReflectionClass($em);
        if ($reflector->hasProperty('delegate')) {
	        $property = $reflector->getProperty('delegate');
	        $property->setAccessible(true);
	        $em = $property->getValue($em);
    	}
        PersistentObject::setObjectManager($em);
    }
}
