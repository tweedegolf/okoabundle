<?php

namespace Tg\OkoaBundle;

use Doctrine\Common\Persistence\PersistentObject;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tg\OkoaBundle\DependencyInjection\EntityEventListenerCompilerPass;
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
        $registry = $this->container->get('doctrine');
        $em = $registry->getManager();
        $this->container->set('em', $em);
        PersistentObject::setObjectManager($em);
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new EntityEventListenerCompilerPass());
    }
}
