<?php

namespace Report\Command;

use Report\Service\{
    Board as BoardService,
    Meeting as MeetingService,
    Member as MemberService,
    Misc as MiscService,
    Organ as OrganService,
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFullCommand extends Command
{
    public function __construct(
        private readonly BoardService $boardService,
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly MiscService $miscService,
        private readonly OrganService $organService,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $output->writeln("generating mailing list tables");
        $this->miscService->generate();

        $output->writeln("generating members table");
        $this->memberService->generate();

        $output->writeln("generating meetings and decision tables");
        $this->meetingService->generate();

        $output->writeln("generating organ tables");
        $this->organService->generate();

        $output->writeln("generating board tables");
        $this->boardService->generate();

        return Command::SUCCESS;
    }
}
