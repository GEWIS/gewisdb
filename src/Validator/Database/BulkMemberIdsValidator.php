<?php

declare(strict_types=1);

namespace App\Validator\Database;

use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function array_key_exists;
use function ctype_digit;
use function is_scalar;
use function trim;

class BulkMemberIdsValidator extends ConstraintValidator
{
    #[Override]
    public function validate(
        mixed $value,
        Constraint $constraint,
    ): void {
        if (!$constraint instanceof BulkMemberIds) {
            throw new UnexpectedTypeException(
                $constraint,
                BulkMemberIds::class,
            );
        }

        if (null === $value) {
            return;
        }

        if (!is_scalar($value)) {
            throw new UnexpectedValueException(
                $value,
                'string',
            );
        }

        $rawMemberIds = trim((string) $value);

        if ('' === $rawMemberIds) {
            $this->context->buildViolation($constraint->emptyMessage)
                ->setCode(BulkMemberIds::EMPTY_ERROR)
                ->addViolation();

            return;
        }

        $seenIds = [];

        foreach (BulkMemberIds::tokenize($rawMemberIds) as $token) {
            if (!ctype_digit($token)) {
                $this->context->buildViolation($constraint->nonNumericMessage)
                    ->setParameter(
                        '{{ value }}',
                        $token,
                    )
                    ->setCode(BulkMemberIds::NON_NUMERIC_ERROR)
                    ->addViolation();

                continue;
            }

            $memberId = (int) $token;

            if (
                array_key_exists(
                    $memberId,
                    $seenIds,
                )
            ) {
                $this->context->buildViolation($constraint->duplicateMessage)
                    ->setParameter(
                        '{{ value }}',
                        (string) $memberId,
                    )
                    ->setCode(BulkMemberIds::DUPLICATE_ERROR)
                    ->addViolation();

                continue;
            }

            $seenIds[$memberId] = true;
        }
    }
}
