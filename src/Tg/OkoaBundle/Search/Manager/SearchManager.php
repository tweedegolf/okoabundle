<?php

namespace Tg\OkoaBundle\Search\Manager;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use RuntimeException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Tg\OkoaBundle\Search\Definition;
use Tg\OkoaBundle\Search\Document;
use Tg\OkoaBundle\Search\Searchable;

abstract class SearchManager implements EventSubscriber
{
    private $em;

    private $changeset = null;

    protected $registry;

    protected $definitions = [];

    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $this->em = $event->getEntityManager();
        $uow = $this->em->getUnitOfWork();

        $this->changeset = [];
        foreach ($uow->getScheduledEntityInsertions() as $insert) {
            if ($insert instanceof Searchable) {
                $this->changeset[] = $insert;
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $update) {
            if ($update instanceof Searchable) {
                $this->documentEntity($update, true, false);
                $this->changeset[] = $update;
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $delete) {
            if ($delete instanceof Searchable) {
                $this->documentEntity($delete, true, false);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event)
    {
        if ($this->em !== $event->getEntityManager()) {
            throw new RuntimeException("Different entity manager found at postFlush compared to onFlush");
        }

        foreach ($this->changeset as $changedEntity) {
            $this->documentEntity($changedEntity, false, true);
        }

        $this->em = null;
        $this->changeset = null;
    }

    private function documentEntity(Searchable $entity, $remove = false, $add = false)
    {
        $metadata = $this->em->getClassMetadata(get_class($entity));
        $ids = $this->getEntityId($entity, $metadata);
        $definition = $this->getIndexDefinition($metadata->getName());

        if ($remove) {
            $this->removeDocument($definition, $ids);
        }

        if ($add) {
            $document = $this->getDocument($entity, $metadata);
            if ($document !== null) {
                $this->addDocument($definition, $ids, $document);
            }
        }
    }

    public function getEntityId($entity, $metadata)
    {
        $ids = $metadata->getIdentifierValues($entity);
        if (count($ids) === 1) {
            $ids = array_values($ids)[0];
        }
        return $ids;
    }

    public function getDocument($entity, $metadata)
    {
        $document = new Document();
        $result = $entity->getSearchDocument($document, $metadata);
        if ($result !== false) {
            if ($document->count() < 1) {
                throw new RuntimeException("Search document needs to contain at least one field");
            }
            return $document;
        }
        return null;
    }

    protected function getEntityManager()
    {
        return $this->em;
    }

    abstract public function removeDocument(Definition $definition, $id);

    abstract public function addDocument(Definition $definition, $id, Document $document);

    public function createIndex($entity)
    {
        $em = $this->registry->getManagerForClass($entity);
        $this->doCreateIndex($this->getIndexDefinition($entity, $em), $em);
    }

    public function createAllIndexes()
    {
        $entities = $this->getSearchableEntities();
        foreach ($entities as $entity) {
            $this->createIndex($entity);
        }
    }

    public function clearIndex($entity)
    {
        $em = $this->registry->getManagerForClass($entity);
        $this->doClearIndex($this->getIndexDefinition($entity, $em), $em);
    }

    public function clearAllIndexes()
    {
        $entities = $this->getSearchableEntities();
        foreach ($entities as $entity) {
            $this->clearIndex($entity);
        }
    }

    public function deleteIndex($entity)
    {
        $em = $this->registry->getManagerForClass($entity);
        $this->doDeleteIndex($this->getIndexDefinition($entity, $em), $em);
    }

    public function deleteAllIndexes()
    {
        $entities = $this->getSearchableEntities();
        foreach ($entities as $entity) {
            $this->deleteIndex($entity);
        }
    }

    public function getSearchableEntities()
    {
        $names = [];
        foreach ($this->registry->getManagers() as $manager) {
            $entities = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($entities as $entity) {
                $name = $entity->getName();
                if (in_array('Tg\OkoaBundle\Search\Searchable', class_implements($name))) {
                    $names[] = $name;
                }
            }
        }
        return $names;
    }

    public function queryCreateAll($entity)
    {
        $em = $this->registry->getManagerForClass($entity);
        $definition = $this->getIndexDefinition($entity, $em);
        $metadata = $definition->getMetadata();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from($metadata->getName(), 'e');
        $query = $qb->getQuery();
        return [
            $query->getResult(),
            $definition,
            $metadata,
        ];
    }

    public function getIndexDefinition($entity, ObjectManager $em = null)
    {
        $obj = null;
        if (is_object($entity)) {
            $obj = $entity;
            $entity = get_class($entity);
        }

        if (!isset($this->definitions[$entity])) {
            if ($em === null) {
                $em = $this->registry->getManagerForClass($entity);
            }

            $metadata = $em->getClassMetadata($entity);

            if ($obj === null) {
                $obj = $metadata->getReflectionClass()->newInstanceWithoutConstructor();
            }

            $definition = new Definition();
            $definition->setMetadata($metadata);
            $obj->getSearchDefinition($definition, $metadata);
            $this->definitions[$entity] = $definition;
        }
        return $this->definitions[$entity];
    }

    abstract protected function doCreateIndex(Definition $definition, ObjectManager $em);

    abstract protected function doClearIndex(Definition $definition, ObjectManager $em);

    abstract protected function doDeleteIndex(Definition $definition, ObjectManager $em);

    public function runTextQuery($entity, $query)
    {
        if (!($entity instanceof Definition)) {
            $entity = $this->getIndexDefinition($entity);
        }
        $em = $this->registry->getManagerForClass($entity->getMetadata()->getName());
        return $this->doRunTextQuery($entity, $em, $query);
    }

    abstract protected function doRunTextQuery(Definition $definition, ObjectManager $em, $query);

    public function getSubscribedEvents()
    {
        return [Events::onFlush, Events::postFlush];
    }
}
