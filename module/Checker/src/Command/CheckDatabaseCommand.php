<?php

namespace Checker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckDatabaseCommand extends AbstractCheckerCommand
{
    protected static $defaultName = 'check:database';
    protected static $defaultDescription = 'Check if the database is sound.';

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->check();

        return Command::SUCCESS;
    }
}
