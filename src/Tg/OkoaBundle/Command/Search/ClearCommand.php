<?php

namespace Tg\OkoaBundle\Command\Search;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends SearchCommand
{
    protected function configure()
    {
        $this
            ->setName('search:index:clear')
            ->setDescription('Clear search indexes')
            ->addArgument('entity', InputArgument::OPTIONAL, 'Clear only a specific entity type\'s index')
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
            $output->writeln("Clearing search index for <info>{$entity}</info>...");
            $manager->clearIndex($entity);
        }
    }
}
