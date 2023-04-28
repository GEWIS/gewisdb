<?php

declare(strict_types=1);

namespace Report\Command;

use Report\Service\Board as BoardService;
use Report\Service\Keyholder as KeyholderService;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\Organ as OrganService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFullCommand extends Command
{
    public function __construct(
        private readonly BoardService $boardService,
        private readonly KeyholderService $keyholderService,
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
        $output->writeln('generating mailing list tables');
        $this->miscService->generate();

        $output->writeln('generating members table');
        $this->memberService->generate();

        $output->writeln('generating meetings and decision tables');
        $this->meetingService->generate();

        $output->writeln('generating organ tables');
        $this->organService->generate();

        $output->writeln('generating board tables');
        $this->boardService->generate();

        $output->writeln('generating keyholder tables');
        $this->keyholderService->generate();

        return Command::SUCCESS;
    }
}
