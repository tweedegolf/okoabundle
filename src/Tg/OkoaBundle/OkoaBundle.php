<?php

namespace Tg\OkoaBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Doctrine\Common\Persistence\PersistentObject;

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
        PersistentObject::setObjectManager($em);
    }
}
