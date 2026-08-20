<?php

declare(strict_types=1);

namespace App\Command\Checker;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

#[AsCommand(
    name: 'check:members:keys',
    description: 'Check and update authentication keys of members when necessary.',
)]
class CheckAuthenticationKeysCommand extends AbstractCheckerCommand
{
    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $revoked = $this->checkerService->checkAuthenticationKeys();

        $output->writeln(sprintf('%d members incorrectly have an authentication key', $revoked));

        return Command::SUCCESS;
    }
}
