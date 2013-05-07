<?php

namespace Tg\OkoaBundle\Search\Manager;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Tg\OkoaBundle\Search\Definition;
use Tg\OkoaBundle\Search\Document;
use Tg\OkoaBundle\Search\Searchable;

/**
 * Class that manages searches and the indexes related to searching.
 * Specific implementations can use different index formats.
 */
abstract class SearchManager implements EventSubscriber
{
    /**
     * The changed entities required for the next postflush since the last onflush
     */
    private $changeset = null;

    /**
     * The doctrine entity manager registry.
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * Cached definition classes for entities.
     * @var Definition[]
     */
    protected $definitions = [];

    /**
     * Set the doctrine entity manager registry.
     * @param  RegistryInterface $registry
     * @return void
     */
    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Calculate the entities that have changed and see if they need to be updated
     * in the search index.
     * @param  OnFlushEventArgs $event
     * @return void
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        $this->changeset = [];
        foreach ($uow->getScheduledEntityInsertions() as $insert) {
            if ($insert instanceof Searchable) {
                $this->changeset[] = $insert;
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $update) {
            if ($update instanceof Searchable) {
                $this->documentEntity($update, $em, true, false);
                $this->changeset[] = $update;
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $delete) {
            if ($delete instanceof Searchable) {
                $this->documentEntity($delete, $em, true, false);
            }
        }
    }

    /**
     * Calculate the entities that have changed and see if they need to be updated
     * in the search index. The postflush event is specifically used for entities
     * that were created, because they don't have an id before this point.
     * @param  PostFlushEventArgs $event
     * @return void
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        foreach ($this->changeset as $changedEntity) {
            $this->documentEntity($changedEntity, $em, false, true);
        }
        $this->changeset = null;
    }

    /**
     * Document the entity by generating a document and removing/inserting it.
     * @param  Searchable    $entity The entity to generate and remove/insert a document for.
     * @param  ObjectManager $em     The ObjectManager associated with the entity.
     * @param  boolean       $remove Whether or not to remove the entity from the index.
     * @param  boolean       $add    Whether or not to add the entity to the index.
     * @return void
     */
    private function documentEntity(Searchable $entity, ObjectManager $em, $remove = false, $add = false)
    {

        $metadata = $em->getClassMetadata(get_class($entity));
        $ids = $this->getEntityId($entity, $metadata);
        $definition = $this->getIndexDefinition($metadata->getName(), $em);

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

    /**
     * Retrieve the id value of the entity.
     * @param  Searchable    $entity   The entity to get the id for.
     * @param  ClassMetadata $metadata The classmetadata for the given entity.
     * @return mixed                   The value of the id column(s).
     */
    public function getEntityId(Searchable $entity, ClassMetadata $metadata)
    {
        $ids = $metadata->getIdentifierValues($entity);
        if (count($ids) === 1) {
            $ids = array_values($ids)[0];
        }
        return $ids;
    }

    /**
     * Generate the Document for a given entity.
     * @param  Searchable    $entity   The entity to get a document for.
     * @param  ClassMetadata $metadata The classmetadata for the given entity.
     * @return Document                Returns null if no document was created
     */
    public function getDocument(Searchable $entity, ClassMetadata $metadata)
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

    /**
     * Update all entities in the search index.
     * @param  Searchable[] $entities List of searchable entities.
     * @return void
     */
    public function updateAll($entities)
    {
        foreach ($entities as $entity) {
            $this->update($entity);
        }
    }

    /**
     * Update a specific object in the index.
     * @param  Searchable $entity The entity to update.
     * @return void
     */
    public function update(Searchable $entity)
    {
        $em = $this->registry->getManagerForClass(get_class($entity));
        $this->documentEntity($entity, $em, true, true);
    }

    /**
     * Create the index for a given entity name.
     * @param  string $entity The entity for which to create an index.
     * @return void
     */
    public function createIndex($entity)
    {
        $em = $this->registry->getManagerForClass($entity);
        $this->doCreateIndex($this->getIndexDefinition($entity, $em), $em);
    }

    /**
     * Create all indexes for all entities.
     * @return void
     */
    public function createAllIndexes()
    {
        $entities = $this->getSearchableEntities();
        foreach ($entities as $entity) {
            $this->createIndex($entity);
        }
    }

    /**
     * Clear the index for a given entity name.
     * @param  string $entity The entity for which to clear an index.
     * @return void
     */
    public function clearIndex($entity)
    {
        $em = $this->registry->getManagerForClass($entity);
        $this->doClearIndex($this->getIndexDefinition($entity, $em), $em);
    }

    /**
     * Clear all indexes for all entities.
     * @return void
     */
    public function clearAllIndexes()
    {
        $entities = $this->getSearchableEntities();
        foreach ($entities as $entity) {
            $this->clearIndex($entity);
        }
    }

    /**
     * Delete the index for a given entity name.
     * @param  string $entity The entity for which to delete an index.
     * @return void
     */
    public function deleteIndex($entity)
    {
        $em = $this->registry->getManagerForClass($entity);
        $this->doDeleteIndex($this->getIndexDefinition($entity, $em), $em);
    }

    /**
     * Delete all indexes for all entities.
     * @return void
     */
    public function deleteAllIndexes()
    {
        $entities = $this->getSearchableEntities();
        foreach ($entities as $entity) {
            $this->deleteIndex($entity);
        }
    }

    /**
     * Retrieve all names of entities that implement the Searchable interface.
     * @return string[] The fully qualified names of all relevant entities.
     */
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

    /**
     * Get a query that selects all entities for a given entity name.
     * @param string $entity Name of the entity
     * @return array An array containing the results, a definition and
     *               the metadata for the entity.
     */
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

    /**
     * Retrieve the index definition for an entity.
     * @param  string        $entity The entity for which to get a definition
     * @param  ObjectManager $em     The objectmanager associated with the entity.
     * @return Definition
     */
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

    /**
     * Run a simple text query for the given entity name.
     * @param  string|Definition $entity The entity for which to run a query
     * @param  string            $query  The query to run
     * @return AbstractQuery
     */
    public function runSimpleQuery($entity, $query)
    {
        return $this->getBuilderForSimpleQuery($entity, $query)->getQuery();
    }

    /**
     * Get a query builder that selects results for the given simple text query.
     * @param  string|Definition $entity The entity for which to run a query
     * @param  string            $query  The query to run
     * @return QueryBuilder
     */
    public function getBuilderForSimpleQuery($entity, $query)
    {
        if (!($entity instanceof Definition)) {
            $entity = $this->getIndexDefinition($entity);
        }
        $em = $this->registry->getManagerForClass($entity->getMetadata()->getName());
        return $this->doGetBuilderForSimpleQuery($entity, $em, $query);
    }

    /**
     * If a document with the given id exists, remove it.
     */
    abstract public function removeDocument(Definition $definition, $id);

    /**
     * Add a document and assign it the given id.
     */
    abstract public function addDocument(Definition $definition, $id, Document $document);

    /**
     * Create a new index with the given definition.
     * @param  Definition    $definition The index definition.
     * @param  ObjectManager $em         The ObjectManager for the original entity.
     * @return void
     */
    abstract protected function doCreateIndex(Definition $definition, ObjectManager $em);

    /**
     * Clear an index of all entries, but keep the index ready for new entries.
     * @param  Definition    $definition The index definition.
     * @param  ObjectManager $em         The ObjectManager for the original entity.
     * @return void
     */
    abstract protected function doClearIndex(Definition $definition, ObjectManager $em);

    /**
     * Delete an index.
     * @param  Definition    $definition The index definition.
     * @param  ObjectManager $em         The ObjectManager for the original entity.
     * @return void
     */
    abstract protected function doDeleteIndex(Definition $definition, ObjectManager $em);

    /**
     * Run a simple text query on an index.
     * @param  Definition    $definition The index definition.
     * @param  ObjectManager $em         The ObjectManager for the original entity.
     * @return QueryBuilder
     */
    abstract protected function doGetBuilderForSimpleQuery(Definition $definition, ObjectManager $em, $query);

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::onFlush, Events::postFlush];
    }
}
