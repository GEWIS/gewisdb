<?php

declare(strict_types=1);

namespace Checker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'check:members:keys',
    description: 'Check and update authentication keys of members when necessary.',
)]
class CheckAuthenticationKeysCommand extends AbstractCheckerCommand
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->checkAuthenticationKeys();

        return Command::SUCCESS;
    }
}
