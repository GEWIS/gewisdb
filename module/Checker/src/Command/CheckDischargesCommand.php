<?php

declare(strict_types=1);

namespace Checker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'check:discharges',
    description: 'Check that no member is installed in a non-existing or abrogated organ.',
)]
class CheckDischargesCommand extends AbstractCheckerCommand
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->checkDischarges();

        return Command::SUCCESS;
    }
}
