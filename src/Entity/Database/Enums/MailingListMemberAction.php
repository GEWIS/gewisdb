<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum MailingListMemberAction: string implements TranslatableInterface
{
    case Add = 'add';
    case Remove = 'remove';

    /**
     * What happened to the subscription, as it reads in the audit trail. Deferred so the caller decides on the
     * locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Add => new TranslatableMessage('Add'),
            self::Remove => new TranslatableMessage('Remove'),
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
