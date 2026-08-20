<?php

declare(strict_types=1);

namespace App\Validator\Database;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Validates a TU/e student number. A student number consists of seven digits and must satisfy the Dutch elfproef.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class StudentNumber extends Constraint
{
    public const string INVALID_FORMAT_ERROR = '1e6cc5a1-6a2c-4a9e-bd0f-2b1d1a0a3d61';
    public const string FAILS_ELFPROEF_ERROR = 'c2d0e5f7-9d3b-4a24-9a4c-2f7c0b6f1a52';

    /** Number of digits in a TU/e student number. */
    public const int LENGTH = 7;

    protected const array ERROR_NAMES = [
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
        self::FAILS_ELFPROEF_ERROR => 'FAILS_ELFPROEF_ERROR',
    ];

    public string $invalidFormatMessage = 'A TU/e student number consists of 7 digits.';

    public string $failsElfproefMessage = 'This does not appear to be a valid TU/e student number. Please check '
        . 'it for typos. If you are certain that it is correct, please contact the secretary.';

    /**
     * @param string[]|null        $groups
     * @param array<string, mixed> $options
     */
    public function __construct(
        ?string $invalidFormatMessage = null,
        ?string $failsElfproefMessage = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        parent::__construct(
            $options,
            $groups,
            $payload,
        );

        $this->invalidFormatMessage = $invalidFormatMessage ?? $this->invalidFormatMessage;
        $this->failsElfproefMessage = $failsElfproefMessage ?? $this->failsElfproefMessage;
    }
}
