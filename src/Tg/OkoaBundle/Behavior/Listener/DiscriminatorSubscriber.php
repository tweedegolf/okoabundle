<?php

namespace Tg\OkoaBundle\Behavior\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Annotations\Reader;

/**
 * Listener for adding a dynamic discriminator column to an entity.
 * Finds all entities that subclass a certain entity and adds those classes
 * to a DiscriminatorMap in the parent.
 */
class DiscriminatorSubscriber implements EventSubscriber
{
    private $reader;

    public function setAnnotationReader(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $metaEvent)
    {
        $metaData = $metaEvent->getClassMetadata();
        $reflector = $metaData->getReflectionClass();

        if ($reflector !== null) {
            $entityAnnotation = $this->reader->getClassAnnotation($reflector, 'Doctrine\ORM\Mapping\Entity');
            $ddiscrAnnotation = $this->reader->getClassAnnotation($reflector, 'Tg\OkoaBundle\Behavior\DynamicDiscriminator');

            if ($entityAnnotation && $ddiscrAnnotation) {
                // assume the base entity has been added manually if the discriminatormap already contains something
                if (count($metaData->discriminatorMap) === 0) {
                    $metaData->discriminatorMap = array($metaData->name => $metaData->name);
                }

                $classes = $metaEvent->getEntityManager()->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
                $name = $metaData->name;
                foreach ($classes as $candidate) {
                    if (is_subclass_of($candidate, $name)) {
                        $metaData->discriminatorMap[$candidate] = $candidate;
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::loadClassMetadata];
    }
}
