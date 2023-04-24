<?php

declare(strict_types=1);

namespace Report\Command;

use Report\Service\{
    Board as BoardService,
    Misc as MiscService,
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePartialCommand extends Command
{
    public function __construct(
        private readonly BoardService $boardService,
        private readonly MiscService $miscService,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $output->writeln("generating misc tables");
        $this->miscService->generate();

        $output->writeln("generating board tables");
        $this->boardService->generate();

        return Command::SUCCESS;
    }
}
