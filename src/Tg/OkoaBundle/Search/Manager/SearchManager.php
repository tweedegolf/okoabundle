<?php

namespace Tg\OkoaBundle\Search\Manager;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use RuntimeException;
use Tg\OkoaBundle\Search\Searchable;
use Tg\OkoaBundle\Search\Document;

abstract class SearchManager implements EventSubscriber
{
    private $em;

    private $changeset = null;

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
        $ids = $metadata->getIdentifierValues($entity);
        if (count($ids) === 1) {
            $ids = array_values($ids)[0];
        }
        $type = $metadata->name;

        if ($remove) {
            $this->removeDocument($type, $ids);
        }

        if ($add) {
            $document = new Document();
            $result = $entity->getSearchDocument($document, $metadata);
            if ($result !== false) {
                if ($document->count() < 1) {
                    throw new RuntimeException("Search document needs to contain at least one field");
                }
                $this->addDocument($type, $ids, $document);
            }
        }
    }

    protected function getEntityManager()
    {
        return $this->em;
    }

    abstract public function removeDocument($type, $id);

    abstract public function addDocument($type, $id, Document $document);

    public function getSubscribedEvents()
    {
        return [Events::onFlush, Events::postFlush];
    }
}
