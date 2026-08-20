<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Enum for the different states a Checkout Session can have.
 */
enum CheckoutSessionStates: int implements TranslatableInterface
{
    case Created = 0;
    case Cancelled = 1;
    case Expired = 2;
    case Pending = 3;
    case Failed = 4;
    case Paid = 5;

    /**
     * The state name, deferred so the caller decides on the locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Created => new TranslatableMessage('Created'),
            self::Cancelled => new TranslatableMessage('Cancelled'),
            self::Expired => new TranslatableMessage('Expired'),
            self::Pending => new TranslatableMessage('Pending'),
            self::Failed => new TranslatableMessage('Failed'),
            self::Paid => new TranslatableMessage('Paid'),
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
