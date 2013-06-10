<?php

namespace Tg\OkoaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class EntityEventListenerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('okoa.doctrine.entity_listener_resolver')) {
            $definition = $container->getDefinition(
                'okoa.doctrine.entity_listener_resolver'
            );

            $taggedServices = $container->findTaggedServiceIds(
                'okoa.entity_event_listener'
            );

            foreach ($taggedServices as $id => $attributes) {
                $definition->addMethodCall(
                    'addListener',
                    [new Reference($id)]
                );
            }
        }
    }
}
