<?php

declare(strict_types=1);

namespace Database\Command;

use Database\Service\Listmonk as ListmonkService;
use Database\Service\Mailman as MailmanService;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:mailinglist:fetch',
    description: 'Fetch mailing lists from mailman and listmonk and store references in GEWISDB.',
)]
class MailingListFetchListsCommand extends Command
{
    private const string ARGUMENT_BACKEND = 'backend';

    public function __construct(
        private readonly ListmonkService $listmonkService,
        private readonly MailmanService $mailmanService,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument(
            self::ARGUMENT_BACKEND,
            InputArgument::OPTIONAL,
            'Target backend: all|mailman|listmonk',
            'all',
        );
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $backend = (string) $input->getArgument(self::ARGUMENT_BACKEND);

        switch ($backend) {
            case 'listmonk':
                $output->writeln('Fetching mailing lists from listmonk:');
                $this->listmonkService->fetchMailingLists();
                break;

            case 'mailman':
                $output->writeln('Fetching mailing lists from mailman:');
                $this->mailmanService->fetchMailingLists();
                break;

            case 'all':
            default:
                $output->writeln('Fetching mailing lists from all backends:');
                $this->mailmanService->fetchMailingLists();
                $this->listmonkService->fetchMailingLists();
                break;
        }

        return Command::SUCCESS;
    }
}
