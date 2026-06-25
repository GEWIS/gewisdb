<?php

declare(strict_types=1);

namespace Database\Form\Validator;

use Laminas\Validator\ValidatorInterface;
use Override;

use function array_key_exists;
use function ctype_digit;
use function preg_split;
use function sprintf;
use function trim;

class BulkMemberIds implements ValidatorInterface
{
    /** @var array<string> */
    private array $messages = [];

    #[Override]
    public function isValid(
        mixed $value,
        ?array $context = null,
    ): bool {
        $this->messages = [];

        $rawMemberIds = trim((string) $value);
        if ('' === $rawMemberIds) {
            $this->messages[] = 'Provide at least one membership number.';

            return false;
        }

        $tokens = preg_split('/[\s,;]+/', $rawMemberIds) ?: [];
        $seenIds = [];

        foreach ($tokens as $token) {
            if ('' === $token) {
                continue;
            }

            if (!ctype_digit($token)) {
                $this->messages[] = sprintf('Non-numeric membership number input: %s', $token);

                continue;
            }

            $memberId = (int) $token;

            if (array_key_exists($memberId, $seenIds)) {
                $this->messages[] = sprintf('Duplicate membership number in input: %s', $memberId);

                continue;
            }

            $seenIds[$memberId] = true;
        }

        return [] === $this->messages;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getMessages(): array
    {
        return $this->messages;
    }
}
