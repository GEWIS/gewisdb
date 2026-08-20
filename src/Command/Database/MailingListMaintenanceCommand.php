<?php

declare(strict_types=1);

namespace App\Command\Database;

use App\Service\Database\MailingListService;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:mailinglist:maintenance',
    description: 'Do administrative maintenance for unusual situations (expired/hidden members).',
)]
class MailingListMaintenanceCommand extends Command
{
    private const string OPTION_FORCE = 'force';

    public function __construct(private readonly MailingListService $mailingListService)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            self::OPTION_FORCE,
            'f',
            InputOption::VALUE_NONE,
            'Perform updates',
        );
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $dryRun = !$input->getOption(self::OPTION_FORCE);

        if ($dryRun) {
            $output->writeln('<info>NOTE</info>: Not using <info>-f</info>, assuming dry-run.');
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            $output->writeln(
                'Implying <info>-vvv</info>, displaying all pending changes',
                OutputInterface::VERBOSITY_VERBOSE,
            );
        }

        $this->mailingListService->performMaintenance(
            $output,
            $dryRun,
        );

        return Command::SUCCESS;
    }
}
