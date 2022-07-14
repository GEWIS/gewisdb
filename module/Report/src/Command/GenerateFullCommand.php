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
    private BoardService $boardService;
    private MeetingService $meetingService;
    private MemberService $memberService;
    private MiscService $miscService;
    private OrganService $organService;

    public function __construct(
        BoardService $boardService,
        MeetingService $meetingService,
        MemberService $memberService,
        MiscService $miscService,
        OrganService $organService,
    ) {
        $this->boardService = $boardService;
        $this->meetingService = $meetingService;
        $this->memberService = $memberService;
        $this->miscService = $miscService;
        $this->organService = $organService;

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
