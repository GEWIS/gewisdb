<?php

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
    private BoardService $boardService;
    private MiscService $miscService;

    public function __construct(
        BoardService $boardService,
        MiscService $miscService,
    ) {
        $this->boardService = $boardService;
        $this->miscService = $miscService;

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
