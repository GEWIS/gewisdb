<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Database\ListmonkService;
use App\Service\Database\MailingListService;
use App\Service\Database\MailmanService;
use App\Service\Database\Member as MemberService;
use App\Service\Report\ApiService;
use DateTime;

use function array_merge;

class FrontPageService
{
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
        return array_merge(
            $this->memberService->getFrontpageData(),
            $this->apiService->getFrontpageData(),
            $this->mailmanService->getFrontpageData(),
            $this->listmonkService->getFrontpageData(),
            $this->mailingListService->getFrontpageData(),
            [
                'totalCount' => $this->getNotificationCount(),
            ],
        );
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
     * Get the total notification count to show in the navbar, not including 'info' messages
     */
    public function getNotificationCount(): int
    {
        return $this->memberService->getPendingUpdateCount() +
        (int) $this->apiService->isSyncPaused() +
        $this->memberService->getPaidProspectivesCount() +
        (int) $this->mailmanService->isLastFetchOverdue() +
        (int) $this->listmonkService->isLastFetchOverdue();
    }
}
