<?php

declare(strict_types=1);

namespace Database\Command;

use Database\Service\Mailman as MailmanService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:mailinglist:fetch',
    description: 'Fetch mailing lists from mailman and store store references in GEWISDB.',
)]
class FetchMailmanListsCommand extends Command
{
    public function __construct(private readonly MailmanService $mailmanService)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->mailmanService->fetchMailingLists();

        return Command::SUCCESS;
    }
}
