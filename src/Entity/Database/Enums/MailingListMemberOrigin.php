<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum MailingListMemberOrigin: string implements TranslatableInterface
{
    case Manual = 'manual';
    case SyncMailman = 'sync_mailman';
    case SyncListmonk = 'sync_listmonk';

    /**
     * What caused the subscription to change, as it reads in the audit trail. Deferred so the caller decides on the
     * locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Manual => new TranslatableMessage('manual'),
            self::SyncMailman => new TranslatableMessage('mailman sync'),
            self::SyncListmonk => new TranslatableMessage('listmonk sync'),
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->getName()->trans(
            $translator,
            $locale,
        );
    }
}
