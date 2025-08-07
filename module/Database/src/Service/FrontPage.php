<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Service\Api as ApiService;
use Database\Service\Mailman as MailmanService;
use Database\Service\Member as MemberService;
use DateTime;

use function array_merge;

class FrontPage
{
    public function __construct(
        private readonly ApiService $apiService,
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
     *   mailmanChangesPending: array{
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
            [
                'totalCount' => $this->getNotificationCount(),
            ],
        );
    }

    /**
     * Get the total notification count to show in the navbar, not including 'info' messages
     */
    public function getNotificationCount(): int
    {
        return $this->memberService->getPendingUpdateCount() +
        (int) $this->apiService->isSyncPaused() +
        $this->memberService->getPaidProspectivesCount() +
        (int) $this->mailmanService->isLastFetchOverdue();
    }
}
