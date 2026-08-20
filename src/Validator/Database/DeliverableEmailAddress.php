<?php

declare(strict_types=1);

namespace App\Validator\Database;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Checks that the host of an e-mail address can actually receive mail. Symfony's Email constraint only checks the
 * syntax, which is not enough for the registration form: an address nobody can reach means a registration that
 * cannot be completed.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class DeliverableEmailAddress extends Constraint
{
    public const string NO_MX_RECORD_ERROR = 'd41c8f1b-9a6e-4c53-8f0d-3c7b5e2a6d19';

    protected const array ERROR_NAMES = [self::NO_MX_RECORD_ERROR => 'NO_MX_RECORD_ERROR'];

    // phpcs:ignore -- user-visible strings should not be split
    public string $message = 'Please check your e-mail address, \'{{ hostname }}\' does not appear to be able to receive e-mails. If you are certain that your e-mail address is correct, please contact the board.';

    /**
     * @param string[]|null        $groups
     * @param array<string, mixed> $options
     */
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        parent::__construct(
            $options,
            $groups,
            $payload,
        );

        $this->message = $message ?? $this->message;
    }
}
