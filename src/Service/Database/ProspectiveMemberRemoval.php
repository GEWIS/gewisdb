<?php

declare(strict_types=1);

namespace App\Service\Database;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * The outcome of removing a prospective member. Everything but `Removed` leaves the prospective member in place.
 */
enum ProspectiveMemberRemoval: string
{
    case Removed = 'removed';

    /** The state of the checkout does not allow removal.  */
    case NotRemovable = 'not-removable';

    /** We could not tell whether the membership fee was already refunded, so nothing was touched. */
    case RefundStatusUnknown = 'refund-status-unknown';

    /** The refund of the membership fee could not be created. */
    case RefundFailed = 'refund-failed';

    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::RefundStatusUnknown => new TranslatableMessage('Unable to check refund status'),
            self::RefundFailed => new TranslatableMessage('Unable to create refund'),
            self::Removed, self::NotRemovable => new TranslatableMessage('This prospective member cannot be removed.'),
        };
    }

    public function getDescription(): TranslatableMessage
    {
        return match ($this) {
            // phpcs:ignore -- user-visible strings should not be split
            self::RefundStatusUnknown => new TranslatableMessage('We were unable to determine whether the prospective member has already received a refund. Please try again later. If this error stays, contact the ApplicatieBeheerCommissie and/or treasurer for more information.'),
            // phpcs:ignore -- user-visible strings should not be split
            self::RefundFailed => new TranslatableMessage('We were unable to create a refund for the prospective member. Please try again later. If this error stays, contact the ApplicatieBeheerCommissie and/or treasurer for more information.'),
            self::Removed, self::NotRemovable => new TranslatableMessage(
                'The state of their membership fee does not allow removal.',
            ),
        };
    }
}
