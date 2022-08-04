<?php

namespace Checker\Model\Exception;

use RuntimeException;

class LookupException extends RuntimeException
{
    /**
     * @param string $message
     * @param int $code
     * @param Throwable $previousException
     */
    public function __construct(string $message, int $code = 0, \Throwable $previousThrowable = null)
    {
        $message = "An error occured during lookup: " . $message;
        parent::__construct($message, $code, $previousThrowable);
    }
}
