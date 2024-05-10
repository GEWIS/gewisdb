<?php

declare(strict_types=1);

namespace Checker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'check:database',
    description: 'Check if the database is sound.',
)]
class CheckDatabaseCommand extends AbstractCheckerCommand
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->check();

        return Command::SUCCESS;
    }
}
