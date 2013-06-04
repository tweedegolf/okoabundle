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
        $this->container->set('em', $em);
        PersistentObject::setObjectManager($em);
    }
}
