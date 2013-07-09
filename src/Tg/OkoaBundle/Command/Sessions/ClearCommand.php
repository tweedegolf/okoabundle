<?php

namespace Tg\OkoaBundle\Command\Sessions;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ClearCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sessions:clear')
            ->setDescription('Clear user sessions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sessionDir = $this->getContainer()->getParameter('session.save_path');
        $filesystem = $this->getContainer()->get('filesystem');

        if (!$filesystem->exists($sessionDir)) {
            $filesystem->mkdir($sessionDir);
        }

        if (!is_writable($sessionDir)) {
            throw new RuntimeException(sprintf('Unable to write in the "%s" directory', $sessionDir));
        }
        $output->writeln('Clearing all sessions');

        $finder = new Finder();
        $filesystem->remove($finder->files()->in($sessionDir));
    }
}
