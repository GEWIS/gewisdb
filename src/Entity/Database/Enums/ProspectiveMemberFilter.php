<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * How far a registration has got, as the prospective-member overview offers it.
 *
 * The state is that of the applicant's most recent checkout session rather than anything stored on the applicant, so
 * these are questions about the payment, not about the person.
 */
enum ProspectiveMemberFilter: string
{
    case All = 'all';

    /** Paid for and waiting on a secretary to set a membership type, which is the only one that needs doing. */
    case Paid = 'paid';

    /** A checkout exists and has not been completed. */
    case AwaitingPayment = 'awaiting-payment';

    /** The checkout expired or failed, or was never created at all. */
    case ExpiredOrFailed = 'expired-or-failed';

    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::All => new TranslatableMessage('All applications'),
            self::Paid => new TranslatableMessage('Paid'),
            self::AwaitingPayment => new TranslatableMessage('Awaiting payment'),
            self::ExpiredOrFailed => new TranslatableMessage('Expired / failed'),
        };
    }
}
