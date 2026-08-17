<?php

declare(strict_types=1);

namespace Database\Form\Validator;

use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\ValidatorInterface;
use Override;

use function preg_match;
use function strlen;

/**
 * Validates a TU/e student number. A student number consists of seven digits and must satisfy the Dutch elfproef.
 */
class StudentNumber implements ValidatorInterface
{
    public const string INVALID_FORMAT = 'studentNumberInvalidFormat';
    public const string FAILS_ELFPROEF = 'studentNumberFailsElfproef';

    /** Number of digits in a TU/e student number. */
    public const int LENGTH = 7;

    /** @var array<string, string> */
    private array $messages = [];

    public function __construct(private readonly Translator $translator)
    {
    }

    #[Override]
    public function isValid(
        mixed $value,
        ?array $context = null,
    ): bool {
        $this->messages = [];

        $studentNumber = (string) $value;

        if (1 !== preg_match('/^\d{' . self::LENGTH . '}$/', $studentNumber)) {
            $this->messages[self::INVALID_FORMAT] = $this->translator->translate(
                'A TU/e student number consists of 7 digits.',
            );

            return false;
        }

        if (!self::passesElfproef($studentNumber)) {
            $this->messages[self::FAILS_ELFPROEF] = $this->translator->translate(
                // phpcs:ignore -- user-visible strings should not be split
                'This does not appear to be a valid TU/e student number. Please check it for typos. If you are certain that it is correct, please contact the secretary.',
            );

            return false;
        }

        return true;
    }

    /**
     * The Dutch elfproef (eleven test). Every digit is weighted by its position, counting down from the length of the
     * number for the leftmost digit to 1 for the rightmost digit. The number is valid if the sum of those products is
     * divisible by eleven. A number consisting of only zeroes trivially satisfies the elfproef, but is not a student
     * number, so it is rejected.
     */
    public static function passesElfproef(string $studentNumber): bool
    {
        $length = strlen($studentNumber);
        $sum = 0;

        for ($position = 0; $position < $length; $position++) {
            $sum += ($length - $position) * (int) $studentNumber[$position];
        }

        return 0 !== $sum && 0 === $sum % 11;
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
