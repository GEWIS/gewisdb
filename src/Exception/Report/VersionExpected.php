<?php

declare(strict_types=1);

namespace App\Exception\Report;

use App\Exception\User\ApiException;
use Throwable;

class VersionExpected extends ApiException
{
    protected int $httpStatusCode = 406;

    public function __construct(
        int $code = 0,
        ?Throwable $previousThrowable = null,
    ) {
        $message = 'API version expected, but none was given';

        parent::__construct(
            $message,
            $code,
            $previousThrowable,
        );
    }
}
