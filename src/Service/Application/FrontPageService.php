<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Database\ListmonkService;
use App\Service\Database\MailingListService;
use App\Service\Database\MailmanService;
use App\Service\Database\Member as MemberService;
use App\Service\Report\ApiService;
use DateTime;
use Override;
use Symfony\Contracts\Service\ResetInterface;

use function array_merge;

class FrontPageService implements ResetInterface
{
    /**
     * What the figures were this request.
     *
     * The bell reads them on every page and the dashboard reads them again, and each of the five services behind
     * them runs its own queries, so without this a page pays for the same counts several times over. Cleared
     * between requests because the application runs in a worker, where the service outlives the request.
     *
     * @var array<string, mixed>|null
     */
    private ?array $data = null;

    public function __construct(
        private readonly ApiService $apiService,
        private readonly ListmonkService $listmonkService,
        private readonly MailingListService $mailingListService,
        private readonly MailmanService $mailmanService,
        private readonly MemberService $memberService,
    ) {
    }

    /**
     * @return array{
     *   members: int,
     *   graduates: int,
     *   expired: int,
     *   prospectives: array{
     *     total: int,
     *     paid: int,
     *   },
     *   updates: int,
     *   syncPaused: bool,
     *   syncPausedUntil: ?DateTime,
     *   totalCount: int,
     *   mailmanLastFetch: ?DateTime,
     *   mailmanLastFetchOverdue: bool,
     *   mailmanLastSync: ?DateTime,
     *   listmonkLastFetch: ?DateTime,
     *   listmonkLastFetchOverdue: bool,
     *   listmonkLastSync: ?DateTime,
     *   mailingListChangesPending: array{
     *      creations: int,
     *      deletions: int,
     *   }
     * }
     */
    public function getFrontpageData(): array
    {
        if (null !== $this->data) {
            return $this->data;
        }

        $data = array_merge(
            $this->memberService->getFrontpageData(),
            $this->apiService->getFrontpageData(),
            $this->mailmanService->getFrontpageData(),
            $this->listmonkService->getFrontpageData(),
            $this->mailingListService->getFrontpageData(),
        );

        // Counted from the figures rather than asked for again: the bell and the dashboard then cannot state
        // different numbers, and it saves running every one of those queries a second time.
        $data['totalCount'] = $data['updates']
            + $data['prospectives']['paid']
            + (int) $data['syncPaused']
            + (int) $data['mailmanLastFetchOverdue']
            + (int) $data['listmonkLastFetchOverdue'];

        return $this->data = $data;
    }

    #[Override]
    public function reset(): void
    {
        $this->data = null;
    }

    /**
     * The same data under the names the dashboard template uses.
     *
     * @return array<string, mixed>
     */
    public function getFrontpageViewData(): array
    {
        $data = $this->getFrontpageData();

        return [
            'members' => $data['members'],
            'graduates' => $data['graduates'],
            'expired' => $data['expired'],
            'prospectives' => $data['prospectives'],
            'updates' => $data['updates'],
            'sync_paused' => $data['syncPaused'],
            'sync_paused_until' => $data['syncPausedUntil'],
            'mailman_last_fetch' => $data['mailmanLastFetch'],
            'mailman_last_fetch_overdue' => $data['mailmanLastFetchOverdue'],
            'listmonk_last_fetch' => $data['listmonkLastFetch'],
            'listmonk_last_fetch_overdue' => $data['listmonkLastFetchOverdue'],
            'mailing_list_changes_pending' => $data['mailingListChangesPending'],
        ];
    }

    /**
     * How many members hold a current membership of each type, for the dashboard's breakdown.
     *
     * @return array<string, int>
     */
    public function getMembershipBreakdown(): array
    {
        return $this->memberService->getMembershipBreakdown();
    }

    /**
     * How many things need someone, which is what the bell shows.
     */
    public function getNotificationCount(): int
    {
        return $this->getFrontpageData()['totalCount'];
    }
}
