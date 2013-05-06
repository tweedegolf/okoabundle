<?php

namespace Tg\OkoaBundle\Command\Search;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryCommand extends SearchCommand
{
    protected function configure()
    {
        $this
            ->setName('search:query')
            ->setDescription('Run a quick query on the search index')
            ->addArgument('entity', InputArgument::REQUIRED, 'Which entity to query')
            ->addArgument('query', InputArgument::REQUIRED, 'Query to execute')
            ->addOption('display', null, InputOption::VALUE_OPTIONAL, 'Number of items to display', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getSearchManager();
        $entity = $input->getArgument('entity');
        $query = $input->getArgument('query');
        $maxdisplay = (int)$input->getOption('display');

        $query = $manager->runTextQuery($entity, $query);
        $results = $query->getResult();

        $count = count($results);
        $display = "";
        if ($count > $maxdisplay) {
            $display = ", displaying {$maxdisplay}";
        }

        if ($count > 0) {
            $display .= ":";
        }
        $output->writeln("Got <info>{$count}</info> result(s){$display}");

        $definition = $manager->getIndexDefinition($entity);
        $name = $definition->getMetadata()->getName();

        $i = 0;
        foreach ($results as $entity) {
            if ($i < $maxdisplay) {
                if (method_exists($entity, '__toString')) {
                    $string = (string)$entity;
                    $output->writeln("<info> -</info> {$string}");
                } else {
                    $id = $definition->getMetadata()->getIdentifierValues($entity);
                    if (count($id) === 1) {
                        $id = array_values($id)[0];
                    } else {
                        $id = '[' . implode(', ', $id) . ']';
                    }
                    $output->writeln("<info> -</info> {$name} <id: {$id}>");
                }
                $i += 1;
            } else {
                break;
            }
        }
    }
}
