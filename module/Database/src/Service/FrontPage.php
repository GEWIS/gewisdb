<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Service\Api as ApiService;
use Database\Service\Member as MemberService;
use DateTime;

use function array_merge;

class FrontPage
{
    public function __construct(
        private readonly ApiService $apiService,
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
     * }
     */
    public function getFrontpageData(): array
    {
        return array_merge(
            $this->memberService->getFrontpageData(),
            $this->apiService->getFrontpageData(),
            [
                'totalCount' => $this->getNotificationCount(),
            ],
        );
    }

    public function getNotificationCount(): int
    {
        return $this->memberService->getPendingUpdateCount() +
        (int) $this->apiService->isSyncPaused() +
        $this->memberService->getPaidProspectivesCount();
    }
}
