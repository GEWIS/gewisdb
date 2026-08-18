<?php

declare(strict_types=1);

namespace App\ViewModel\Application;

use Symfony\Component\Translation\TranslatableMessage;

use function Symfony\Component\Translation\t;

/**
 * One thing that needs someone, as the bell and the dashboard both state it.
 *
 * Assembled from the front page's figures rather than stored: each of these is a question about the register or an
 * integration that is answered fresh every time it is asked.
 */
final readonly class Notification
{
    private function __construct(
        public string $icon,
        public string $tone,
        public TranslatableMessage $message,
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

        // `%count%` is not used as a placeholder anywhere here: the translator reads it as a plural selector and
        // would try to choose between variants that these messages do not have.
        if (0 < $data['prospectives']['paid']) {
            $notifications[] = new self(
                'fa-user-check',
                'warning',
                t('%number% prospective members have paid and are waiting for approval.', [
                    '%number%' => $data['prospectives']['paid'],
                ]),
                'join_prospective_member_index',
            );
        }

        if (0 < $data['updates']) {
            $notifications[] = new self(
                'fa-user-pen',
                'info',
                t('%number% members have requested a change to their data.', ['%number%' => $data['updates']]),
                'member_update_index',
            );
        }

        // A paused sync is deliberate, but it stays visible so it is not forgotten about.
        if (true === $data['sync_paused']) {
            $notifications[] = new self(
                'fa-pause',
                'info',
                t('Mailing list synchronisation is paused.'),
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
                t('%service% lists have not been fetched recently.', ['%service%' => $label]),
                'mailing_list_index',
            );
        }

        return $notifications;
    }
}
