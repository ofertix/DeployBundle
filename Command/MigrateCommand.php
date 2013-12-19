<?php

/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class MigrateCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deployer:migrate')
            ->addArgument('version',InputArgument::OPTIONAL,'Specified version to migrate')
            ->setDescription('Run doctrine migrations.')
            ->setHelp(<<<EOT
The <info>deployer:migrate</info> command will run a migration to a specified version or the latest avaliable version.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');

        if($input->isInteractive()) {
            $dialog = $this->getHelperSet()->get('dialog');
            $confirmation = $dialog->askConfirmation($output, '<question>WARNING! You are about to migrate the database. Are you sure you wish to continue? (y/n)</question>', false);
        }
        else $confirmation = true;

        if ($confirmation === true) {
            $this->deployer->runMigration($version);
        } else {
            $output->writeln('<error>Migration cancelled!</error>');
        }
    }
}