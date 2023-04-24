<?php

declare(strict_types=1);

namespace Checker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckAuthenticationKeysCommand extends AbstractCheckerCommand
{
    protected static $defaultName = 'check:members:keys';
    protected static $defaultDescription = 'Check and update authentication keys of members when necessary.';

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->checkAuthenticationKeys();

        return Command::SUCCESS;
    }
}
