<?php

declare(strict_types=1);

namespace App\Command\Report;

use App\Service\Report\ApiService;
use App\Service\Report\MeetingService;
use App\Service\Report\MemberService;
use App\Service\Report\MiscService;
use Closure;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'report:generate:full',
    description: 'Rebuild ReportDB from scratch by replaying all of GEWISDB.',
)]
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
            $this->miscService->generateLists();

            // Generating a member generates their mailing list memberships along with them, which is why the lists
            // themselves have to exist by now.
            $output->writeln('generating members table');
            $this->withProgressBar(
                $output,
                $this->memberService->generate(...),
            );

            // Replaying the meetings builds the decision tables and, along with them, everything derived from those
            // decisions: organs and their members, board members, and keyholders.
            $output->writeln('replaying meetings and decisions');
            $this->withProgressBar(
                $output,
                $this->meetingService->generate(...),
            );
        } finally {
            if (!$syncWasPaused) {
                $this->apiService->resumeSyncNow();
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Run a generation step behind a progress bar.
     *
     * The report services report progress through a callback instead of writing to the console themselves, and only
     * know how much work there is once they have started, so the bar takes its size from the first callback rather
     * than from before the call. Progress is not necessarily reported for every unit of work either, hence finishing
     * the bar here instead of relying on it reaching its maximum on its own.
     *
     * @param Closure((Closure(int $current, int $total): void)|null): void $generate
     */
    private function withProgressBar(
        OutputInterface $output,
        Closure $generate,
    ): void {
        $progressBar = new ProgressBar($output);

        $generate(static function (int $current, int $total) use ($progressBar): void {
            if (0 === $progressBar->getMaxSteps()) {
                $progressBar->start($total);
            }

            $progressBar->setProgress($current);
        });

        $progressBar->finish();
        $output->writeln('');
    }
}
