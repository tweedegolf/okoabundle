<?php

namespace Tg\OkoaBundle\Command\Search;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InsertCommand extends SearchCommand
{
    protected function configure()
    {
        $this
            ->setName('search:index:insert')
            ->setDescription('Insert all entities into an empty search index')
            ->addArgument('entity', InputArgument::OPTIONAL, 'Insert entities for only a specific type')
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
            $this->insertIntoIndex($manager, $entity, $output);
        }
    }
}
