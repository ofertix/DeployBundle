<?php

namespace JordiLlonch\Bundle\DeployBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class MigrateCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deployer:migrate')
            ->setDescription('Migrate databases to configured servers.')
            ->setHelp(<<<EOT
The <info>deployer:migrate</info> command migrate database schema to all configured servers.
EOT
            );
        $this->addArgument('version', InputArgument::OPTIONAL, 'Version to migrate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');

        $this->deployer->runMigrateCode($version);
    }
}