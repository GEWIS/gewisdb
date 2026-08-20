<?php

declare(strict_types=1);

namespace App\Service\Database;

/**
 * Why a prospective member could not be sent back to the checkout.
 */
enum CheckoutRestartFailure: string
{
    /** The payment link is unknown, or has already been used to complete a payment. */
    case LinkUnusable = 'link-unusable';

    /** The link is fine, but no Checkout Session could be recovered or created for it. */
    case CheckoutUnavailable = 'checkout-unavailable';
}
