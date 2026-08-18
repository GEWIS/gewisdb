<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Service\Api\FrontPageService;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ApplicationExtension extends AbstractExtension
{
    public function __construct(private readonly FrontPageService $frontPageService)
    {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('notification_count', $this->notificationCount(...)),
        ];
    }

    public function notificationCount(): int
    {
        return $this->frontPageService->getNotificationCount();
    }
}
