<?php

declare(strict_types=1);

namespace App\ViewModel\Application;

use Symfony\Component\Translation\TranslatableMessage;

use function Symfony\Component\Translation\t;

/**
 * One thing that needs someone, as the bell and the dashboard both state it.
 *
 * Assembled from the front page's figures rather than stored: each of these is a question about the register or an
 * integration that is answered fresh every time it is asked. The bell shows `$message` on its own; the dashboard puts
 * `$title` above it and offers `$action` as the way in, so both read the same set without either restating it.
 */
final readonly class Notification
{
    private function __construct(
        public string $icon,
        public string $tone,
        public TranslatableMessage $title,
        public TranslatableMessage $kind,
        public TranslatableMessage $message,
        public TranslatableMessage $action,
        public string $route,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return self[]
     */
    public static function fromFrontPage(array $data): array
    {
        $notifications = [];
        $approval = t('Approval');
        $integration = t('Integration');
        $review = t('Review');

        // `%count%` is not used as a placeholder anywhere here: the translator reads it as a plural selector and
        // would try to choose between variants that these messages do not have.
        if (0 < $data['prospectives']['paid']) {
            $notifications[] = new self(
                'fa-user-check',
                'warning',
                t(
                    '%number% prospective members have paid',
                    ['%number%' => $data['prospectives']['paid']],
                ),
                $approval,
                t(
                    '%number% prospective members have paid and are waiting for approval.',
                    [
                        '%number%' => $data['prospectives']['paid'],
                    ],
                ),
                $review,
                'join_prospective_member_index',
            );
        }

        if (0 < $data['updates']) {
            $notifications[] = new self(
                'fa-user-pen',
                'info',
                t(
                    '%number% member updates pending',
                    ['%number%' => $data['updates']],
                ),
                $approval,
                t(
                    '%number% members have requested a change to their data.',
                    ['%number%' => $data['updates']],
                ),
                $review,
                'member_update_index',
            );
        }

        // A paused sync is deliberate, but it stays visible so it is not forgotten about.
        if (true === $data['sync_paused']) {
            $notifications[] = new self(
                'fa-pause',
                'info',
                t('Synchronisation is paused'),
                $integration,
                t('Mailing list synchronisation is paused.'),
                t('Settings'),
                'application_settings_index',
            );
        }

        foreach (['mailman' => 'Mailman', 'listmonk' => 'Listmonk'] as $key => $label) {
            if (true !== $data[$key . '_last_fetch_overdue']) {
                continue;
            }

            $notifications[] = new self(
                'fa-envelope',
                'danger',
                t(
                    '%service% lists are not being fetched',
                    ['%service%' => $label],
                ),
                $integration,
                t(
                    '%service% lists have not been fetched recently. The mailing list server may be down, '
                    . 'or fetching may be failing.',
                    [
                        '%service%' => $label,
                    ],
                ),
                t('Diagnose'),
                'mailing_list_index',
            );
        }

        return $notifications;
    }
}
