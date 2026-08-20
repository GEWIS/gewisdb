<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * The states a member record can be in, as the member overview offers them.
 *
 * These are not stored anywhere: each one is a way of asking the same table a different question, so a secretary can
 * get to the records that need doing something about without writing a query.
 */
enum MemberFilter: string
{
    case Everyone = 'everyone';

    /** Holds a membership that has started and has not ended. */
    case Active = 'active';

    /** Held a membership once; it has since lapsed. */
    case Expired = 'expired';

    /** Complete enough to exist, not complete enough to reach: no e-mail, or ordinary without a student number. */
    case MissingData = 'missing-data';

    /** Deleted, but kept so the decisions that mention them stay readable. */
    case Removed = 'removed';

    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Everyone => new TranslatableMessage('Everyone'),
            self::Active => new TranslatableMessage('Active'),
            self::Expired => new TranslatableMessage('Expired'),
            self::MissingData => new TranslatableMessage('Missing data'),
            self::Removed => new TranslatableMessage('Removed'),
        };
    }
}
