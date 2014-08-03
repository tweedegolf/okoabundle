<?php

namespace Tg\OkoaBundle\Command\Search;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends SearchCommand
{
    protected function configure()
    {
        $this
            ->setName('search:index:delete')
            ->setDescription('Delete search indexes')
            ->addArgument('entity', InputArgument::OPTIONAL, 'Delete only a specific entity type\'s index')
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
            $output->writeln("Deleting search index for <info>{$entity}</info>...");
            $manager->deleteIndex($entity);
        }
    }
}
