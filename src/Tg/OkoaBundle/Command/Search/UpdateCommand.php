<?php

namespace Tg\OkoaBundle\Command\Search;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends SearchCommand
{
    protected function configure()
    {
        $this
            ->setName('search:index:update')
            ->setDescription('Update search indexes')
            ->addArgument('entity', InputArgument::OPTIONAL, 'Update only a specific entity type\'s index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getSearchManager();
        $entity = $input->getArgument('entity');
        if (is_string($entity)) {
            $entities = [$entity];
        } else {
            $entities = $manager->getSearchableEntities();
        }
        foreach ($entities as $entity) {
            $output->writeln("Updating search index for <info>{$entity}</info>...");
            $output->writeln("Deleting old data...");
            $manager->deleteIndex($entity);
            $output->writeln("Creating new index...");
            $manager->createIndex($entity);
            // find all entities and insert them
            $this->insertIntoIndex($manager, $entity, $output);
        }
    }
}
