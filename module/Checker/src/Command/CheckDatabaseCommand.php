<?php

declare(strict_types=1);

namespace Checker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckDatabaseCommand extends AbstractCheckerCommand
{
    /** @var string $defaultName */
    protected static $defaultName = 'check:database';
    /** @var string $defaultDescription */
    protected static $defaultDescription = 'Check if the database is sound.';

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->check();

        return Command::SUCCESS;
    }
}
