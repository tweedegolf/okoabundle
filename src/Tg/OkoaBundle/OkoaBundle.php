<?php

namespace Tg\OkoaBundle;

use Doctrine\Common\Persistence\PersistentObject;
use ReflectionClass;
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

        $resolver = $this->container->get('okoa.doctrine.entity_listener_resolver');
        foreach ($registry->getManagers() as $name => $manager) {
            $manager->getConfiguration()->setEntityListenerResolver($resolver);
        }
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new EntityEventListenerCompilerPass());
    }
}
