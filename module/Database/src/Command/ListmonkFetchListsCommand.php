<?php

declare(strict_types=1);

namespace Database\Command;

use Database\Service\Listmonk as ListmonkService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:listmonk:fetch',
    description: 'Fetch mailing lists from listmonk and store store references in GEWISDB.',
)]
class ListmonkFetchListsCommand extends Command
{
    public function __construct(private readonly ListmonkService $listmonkService)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->listmonkService->fetchMailingLists();

        return Command::SUCCESS;
    }
}