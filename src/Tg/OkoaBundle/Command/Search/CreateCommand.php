<?php

namespace Tg\OkoaBundle\Command\Search;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends SearchCommand
{
    protected function configure()
    {
        $this
            ->setName('search:index:create')
            ->setDescription('Create search indexes')
            ->addArgument('entity', InputArgument::OPTIONAL, 'Create only a specific entity type\'s index')
            ->addOption('insert', null, InputOption::VALUE_NONE, 'Insert all documents into the index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getSearchManager();
        $insert = $input->getOption('insert');
        $entity = $input->getArgument('entity');
        if (is_string($entity)) {
            $entities = [$entity];
        } else {
            $entities = $manager->getSearchableEntities();
        }
        foreach ($entities as $entity) {
            $output->writeln("Creating search index for <info>{$entity}</info>...");
            $manager->createIndex($entity);

            if ($insert) {
                $this->insertIntoIndex($manager, $entity, $output);
            }
        }
    }
}
