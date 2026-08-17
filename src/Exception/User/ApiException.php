<?php

declare(strict_types=1);

namespace App\Exception\User;

use RuntimeException;

/**
 * Abstract class for API exceptions
 */
abstract class ApiException extends RuntimeException
{
    protected int $httpStatusCode = 500;

    final public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
