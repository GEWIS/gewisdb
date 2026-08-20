<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Service\Application\FrontPageService;
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
            new TwigFunction(
                'notification_count',
                $this->notificationCount(...),
            ),
            new TwigFunction(
                'prospective_awaiting_approval',
                $this->prospectiveAwaitingApproval(...),
            ),
        ];
    }

    public function notificationCount(): int
    {
        return $this->frontPageService->getNotificationCount();
    }

    /**
     * Prospective members who have paid and are waiting for the secretary to set a membership type.
     *
     * Read off the front page figures rather than counted again: the sidebar badge and the bell are then the same
     * number, and the page pays for the query once.
     */
    public function prospectiveAwaitingApproval(): int
    {
        return $this->frontPageService->getFrontpageData()['prospectives']['paid'];
    }
}
