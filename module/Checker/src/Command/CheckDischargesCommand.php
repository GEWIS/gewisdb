<?php

namespace Checker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckDischargesCommand extends AbstractCheckerCommand
{
    protected static $defaultName = 'check:discharges';
    protected static $defaultDescription = 'Check that no member is installed in a non-existing or abrogated organ.';

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->checkDischarges();

        return Command::SUCCESS;
    }
}
