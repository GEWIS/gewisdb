<?php

declare(strict_types=1);

namespace Database\Model\Exception;

use PHLAK\SemVer\Version as SemanticVersion;
use Throwable;
use User\Model\Exception\ApiException;

use function sprintf;

class VersionIncompatible extends ApiException
{
    protected int $httpStatusCode = 406;

    public function __construct(
        SemanticVersion $lower,
        ?SemanticVersion $upper,
        SemanticVersion $given,
        int $code = 0,
        ?Throwable $previousThrowable = null,
    ) {
        if (null === $upper) {
            $message = sprintf(
                'API version must be at least %s, but %s was given.',
                $lower,
                $given,
            );
        } else {
            $message = sprintf(
                'API version must be between %s and %s, but %s was given.',
                $lower,
                $upper,
                $given,
            );
        }

        parent::__construct($message, $code, $previousThrowable);
    }
}
