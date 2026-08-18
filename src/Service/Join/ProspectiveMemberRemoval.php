<?php

declare(strict_types=1);

namespace App\Service\Join;

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
}
