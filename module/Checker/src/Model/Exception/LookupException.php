<?php

declare(strict_types=1);

namespace Checker\Model\Exception;

use RuntimeException;
use Throwable;

class LookupException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previousThrowable = null,
    ) {
        $message = 'An error occured during lookup: ' . $message;

        parent::__construct($message, $code, $previousThrowable);
    }
}
