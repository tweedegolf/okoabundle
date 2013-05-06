<?php

namespace Tg\OkoaBundle\Command\Search;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Tg\OkoaBundle\Search\Manager\SearchManager;

abstract class SearchCommand extends ContainerAwareCommand
{

    public function insertIntoIndex(SearchManager $manager, $entity, OutputInterface $output)
    {
        $output->writeln("Querying for entities to be inserted...");
        list($results, $definition, $metadata) = $manager->queryCreateAll($entity);
        $count = count($results);

        $output->writeln("Searching through <info>{$count}</info> for documents to insert...");
        $output->write("Inserted <info>0</info> documents...");

        $added = 0;
        foreach ($results as $result) {
            $document = $manager->getDocument($result, $metadata);
            if ($document !== null) {
                $ids = $manager->getEntityId($result, $metadata);
                $manager->addDocument($definition, $ids, $document);
                $added += 1;
                $output->write("\rInserted <info>{$added}</info> documents...");
            }
        }
        $output->writeln("");
    }

    public function getSearchManager()
    {
        return $this->getContainer()->get('okoa.search_manager');
    }
}
