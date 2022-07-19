<?php

namespace Checker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckMembershipExpirationCommand extends AbstractCheckerCommand
{
    protected static $defaultName = 'check:membership:expiration';
    protected static $defaultDescription = 'Check and update memberships expirations when necessary.';

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->checkNormalExpiration();

        return Command::SUCCESS;
    }
}
