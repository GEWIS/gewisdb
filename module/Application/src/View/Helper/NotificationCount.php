<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Database\Service\FrontPage as FrontPageService;
use Laminas\View\Helper\AbstractHelper;

class NotificationCount extends AbstractHelper
{
    public function __construct(protected readonly FrontPageService $frontPageService)
    {
    }

    /**
     * Get the current number of notifications.
     */
    public function __invoke(): int
    {
        return $this->frontPageService->getNotificationCount();
    }
}
