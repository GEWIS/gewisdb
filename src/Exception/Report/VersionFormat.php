<?php

declare(strict_types=1);

namespace App\Exception\Report;

use App\Exception\User\ApiException;
use Throwable;

use function rawurlencode;
use function sprintf;

class VersionFormat extends ApiException
{
    protected int $httpStatusCode = 406;

    public function __construct(
        string $given,
        int $code = 0,
        ?Throwable $previousThrowable = null,
    ) {
        $message = sprintf(
            'API version expected, but %s was given',
            rawurlencode($given),
        );

        parent::__construct(
            $message,
            $code,
            $previousThrowable,
        );
    }
}
