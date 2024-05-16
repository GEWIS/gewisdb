<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Database\Service\FrontPage as FrontPageService;
use Laminas\View\Helper\AbstractHelper;
use Psr\Container\ContainerInterface;

class NotificationCount extends AbstractHelper
{
    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    /**
     * Get the current number of notifications.
     */
    public function __invoke(): int
    {
        $frontPageService = $this->container->get(FrontPageService::class);

        return $frontPageService->getNotificationCount();
    }
}
