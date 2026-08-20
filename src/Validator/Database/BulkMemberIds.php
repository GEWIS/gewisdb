<?php

declare(strict_types=1);

namespace App\Validator\Database;

use Attribute;
use Symfony\Component\Validator\Constraint;

use function preg_split;
use function trim;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Validates a free-form list of membership numbers: every token must be numeric and may occur only once.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class BulkMemberIds extends Constraint
{
    public const string EMPTY_ERROR = '0f0b2b62-6f0a-4a3e-9e2f-4c1e0b1a8f3d';
    public const string NON_NUMERIC_ERROR = '7a4c1f2e-3b6d-4f18-8a5b-6d2c9e0f4a71';
    public const string DUPLICATE_ERROR = 'b3d9c0a4-5e12-4f7a-9c3d-8f6b2a1e5c04';

    protected const array ERROR_NAMES = [
        self::EMPTY_ERROR => 'EMPTY_ERROR',
        self::NON_NUMERIC_ERROR => 'NON_NUMERIC_ERROR',
        self::DUPLICATE_ERROR => 'DUPLICATE_ERROR',
    ];

    public string $emptyMessage = 'Provide at least one membership number.';

    public string $nonNumericMessage = 'Non-numeric membership number input: {{ value }}';

    public string $duplicateMessage = 'Duplicate membership number in input: {{ value }}';

    /**
     * @param string[]|null        $groups
     * @param array<string, mixed> $options
     */
    public function __construct(
        ?string $emptyMessage = null,
        ?string $nonNumericMessage = null,
        ?string $duplicateMessage = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        parent::__construct(
            $options,
            $groups,
            $payload,
        );

        $this->emptyMessage = $emptyMessage ?? $this->emptyMessage;
        $this->nonNumericMessage = $nonNumericMessage ?? $this->nonNumericMessage;
        $this->duplicateMessage = $duplicateMessage ?? $this->duplicateMessage;
    }

    /**
     * Split raw input into membership number tokens.
     *
     * The separator set is the contract between this constraint and everything that later parses the input, so both
     * sides tokenise through here.
     *
     * @return string[]
     */
    public static function tokenize(string $rawMemberIds): array
    {
        $tokens = preg_split(
            '/[\s,;]+/',
            trim($rawMemberIds),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        return false === $tokens
            ? []
            : $tokens;
    }
}
