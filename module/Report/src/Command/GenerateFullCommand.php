<?php

declare(strict_types=1);

namespace Report\Command;

use Database\Service\Api as ApiService;
use Override;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFullCommand extends Command
{
    /**
     * How long the API is kept from syncing while ReportDB is being rebuilt.
     *
     * Generous on purpose: the pause is lifted again as soon as the rebuild is done, so the only thing this bounds is
     * how long a crashed run can keep the API waiting.
     */
    private const int SYNC_PAUSE_MINUTES = 120;

    public function __construct(
        private readonly ApiService $apiService,
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly MiscService $miscService,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        // Halfway through a rebuild ReportDB describes a moment in the past, so nothing should be syncing from it. A
        // pause somebody else set is left alone, including how long it still has to run.
        $syncWasPaused = $this->apiService->isSyncPaused();
        $this->apiService->pauseSync(self::SYNC_PAUSE_MINUTES);

        try {
            $output->writeln('generating mailing list tables');
            $this->miscService->generate();

            $output->writeln('generating members table');
            $this->memberService->generate();

            // Replaying the meetings builds the decision tables and, along with them, everything derived from those
            // decisions: organs and their members, board members, and keyholders.
            $output->writeln('replaying meetings and decisions');
            $this->meetingService->generate();
        } finally {
            if (!$syncWasPaused) {
                $this->apiService->resumeSyncNow();
            }
        }

        return Command::SUCCESS;
    }
}
