<?php

declare(strict_types=1);

namespace Database\Command;

use Database\Service\Mailman as MailmanService;
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Laminas\Cli\Input\BoolParam;
use Laminas\Cli\Input\ParamAwareInputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:mailman:syncmembership',
    description: 'Sync all pending mailing list changes.',
)]
class MailmanSyncMembershipCommand extends AbstractParamAwareCommand
{
    private const PARAM_FORCE = 'force';

    public function __construct(private readonly MailmanService $mailmanService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addParam(
            (new BoolParam(self::PARAM_FORCE))
                ->setDescription('Perform actions in mailman')
                ->setShortcut('f')
                ->setDefault(true),
        );
    }

    /**
     * @param ParamAwareInputInterface $input
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $dryRun = !$input->getParam(self::PARAM_FORCE);

        if ($dryRun) {
            $output->writeln('<info>NOTE</info>: Not using <info>-f</info>, assuming dry-run.');
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            $output->writeln(
                'Implying <info>-vvv</info>, displaying all pending changes',
                OutputInterface::VERBOSITY_VERBOSE,
            );
        }

        $output->writeln('Syncing mailman mailing list membership:');
        $this->mailmanService->syncMembership($output, $dryRun);

        return Command::SUCCESS;
    }
}
