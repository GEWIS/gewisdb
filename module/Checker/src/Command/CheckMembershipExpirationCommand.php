<?php

declare(strict_types=1);

namespace Checker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'check:membership:expiration',
    description: 'Check and update memberships expirations when necessary.',
)]
class CheckMembershipExpirationCommand extends AbstractCheckerCommand
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getCheckerService()->checkNormalExpiration();

        return Command::SUCCESS;
    }
}
