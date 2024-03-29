<?php

declare(strict_types=1);

namespace Checker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckMembershipTUeCommand extends AbstractCheckerCommand
{
    /** @var string $defaultName */
    protected static $defaultName = 'check:membership:tue';
    /** @var string $defaultDescription */
    protected static $defaultDescription = 'Check whether ordinary members are still studying at the TU/e.';

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->checkAtTUe();

        return Command::SUCCESS;
    }
}
