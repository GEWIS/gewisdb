<?php

declare(strict_types=1);

namespace App\Twig\Components\Application;

use App\Service\Application\FrontPageService;
use App\ViewModel\Application\Notification;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

use function count;

/**
 * What is waiting for someone, as a bell in the navbar.
 *
 * GEWISDB has no notification records: nothing is addressed to a person and nothing is marked read. What the bell
 * counts is the state of the register and its integrations right now, which is the same set the dashboard opens with,
 * so there is nothing to dismiss and the count goes down by dealing with the thing rather than by looking at it.
 */
#[AsTwigComponent(
    name: 'Application:NotificationBell',
    template: 'components/Application/NotificationBell.html.twig',
)]
final class NotificationBell
{
    public function __construct(private readonly FrontPageService $frontPageService)
    {
    }

    /**
     * @return Notification[]
     */
    public function getNotifications(): array
    {
        return Notification::fromFrontPage($this->frontPageService->getFrontpageViewData());
    }

    public function getCount(): int
    {
        return count($this->getNotifications());
    }
}
