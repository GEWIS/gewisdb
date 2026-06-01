<?php

declare(strict_types=1);

namespace Database\Command;

use Database\Service\Listmonk as ListmonkService;
use Database\Service\MailingList as MailingListService;
use Database\Service\Mailman as MailmanService;
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Laminas\Cli\Input\BoolParam;
use Laminas\Cli\Input\ParamAwareInputInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:mailinglist:sync-membership',
    description: 'Sync mailing list memberships (backends: all, local, mailman, listmonk).',
)]
class MailingListSyncCommand extends AbstractParamAwareCommand
{
    private const string PARAM_FORCE = 'force';
    private const string ARGUMENT_BACKEND = 'backend';

    public function __construct(
        private readonly MailingListService $mailingListService,
        private readonly MailmanService $mailmanService,
        private readonly ListmonkService $listmonkService,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument(
            self::ARGUMENT_BACKEND,
            InputArgument::OPTIONAL,
            'Target backend: all|local|mailman|listmonk',
            'all',
        );

        $this->addParam(
            (new BoolParam(self::PARAM_FORCE))
                ->setDescription('Perform actions')
                ->setShortcut('f')
                ->setDefault(true),
        );
    }

    /**
     * @param ParamAwareInputInterface $input
     */
    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $backend = (string) $input->getArgument(self::ARGUMENT_BACKEND);
        $dryRun = !$input->getParam(self::PARAM_FORCE);

        if ($dryRun) {
            $output->writeln('<info>NOTE</info>: Not using <info>-f</info>, assuming dry-run.');
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            $output->writeln(
                'Implying <info>-vvv</info>, displaying all pending changes',
                OutputInterface::VERBOSITY_VERBOSE,
            );
        }

        switch ($backend) {
            case 'local':
                $output->writeln('Syncing local mailing list membership:');
                $this->mailingListService->syncLocalOnlyMembership($output, $dryRun);
                break;

            case 'mailman':
                $output->writeln('Syncing mailman mailing list membership:');
                $this->mailmanService->syncMembership($output, $dryRun);
                break;

            case 'listmonk':
                $output->writeln('Syncing listmonk mailing list membership:');
                $this->listmonkService->syncMembership($output, $dryRun);
                break;

            case 'all':
            default:
                $output->writeln('Syncing all mailing list backends:');
                $this->mailingListService->syncLocalOnlyMembership($output, $dryRun);
                $this->mailmanService->syncMembership($output, $dryRun);
                $this->listmonkService->syncMembership($output, $dryRun);
                break;
        }

        return Command::SUCCESS;
    }
}
