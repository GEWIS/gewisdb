<?php

declare(strict_types=1);

namespace Database\Model\Enums;

/**
 * Enum for the different statuses an API response can have
 */
enum ApiResponseStatuses: string
{
    // For 2xx codes
    case Success = 'success';

    // For 404 HTTP code
    case NotFound = 'notfound';

    // For 5xx HTTP codes
    case Error = 'error';
}
