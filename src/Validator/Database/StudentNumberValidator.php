<?php

declare(strict_types=1);

namespace App\Validator\Database;

use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_scalar;
use function preg_match;
use function strlen;

class StudentNumberValidator extends ConstraintValidator
{
    #[Override]
    public function validate(
        mixed $value,
        Constraint $constraint,
    ): void {
        if (!$constraint instanceof StudentNumber) {
            throw new UnexpectedTypeException(
                $constraint,
                StudentNumber::class,
            );
        }

        if (
            null === $value
            || '' === $value
        ) {
            return;
        }

        if (!is_scalar($value)) {
            throw new UnexpectedValueException(
                $value,
                'string',
            );
        }

        $studentNumber = (string) $value;

        if (
            1 !== preg_match(
                '/^\d{' . StudentNumber::LENGTH . '}$/',
                $studentNumber,
            )
        ) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->setCode(StudentNumber::INVALID_FORMAT_ERROR)
                ->addViolation();

            return;
        }

        if (self::passesElfproef($studentNumber)) {
            return;
        }

        $this->context->buildViolation($constraint->failsElfproefMessage)
            ->setCode(StudentNumber::FAILS_ELFPROEF_ERROR)
            ->addViolation();
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
}
