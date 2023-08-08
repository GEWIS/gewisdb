<?php

declare(strict_types=1);

namespace Database\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different states a Checkout Session can have.
 */
enum CheckoutSessionStates: int
{
    case Created = 0;
    case Cancelled = 1;
    case Expired = 2;
    case Pending = 3;
    case Failed = 4;
    case Paid = 5;

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Created => $translator->translate('Created'),
            self::Cancelled => $translator->translate('Cancelled'),
            self::Expired => $translator->translate('Expired'),
            self::Pending => $translator->translate('Pending'),
            self::Failed => $translator->translate('Failed'),
            self::Paid => $translator->translate('Paid'),
        };
    }
}
