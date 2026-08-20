<?php

declare(strict_types=1);

namespace App\Validator\Database;

use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function gethostbynamel;
use function getmxrr;
use function is_array;
use function is_scalar;
use function strrpos;
use function substr;

class DeliverableEmailAddressValidator extends ConstraintValidator
{
    #[Override]
    public function validate(
        mixed $value,
        Constraint $constraint,
    ): void {
        if (!$constraint instanceof DeliverableEmailAddress) {
            throw new UnexpectedTypeException(
                $constraint,
                DeliverableEmailAddress::class,
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

        $address = (string) $value;

        // Anything that is not shaped like an address is reported by the syntax constraint instead.
        $separator = strrpos(
            $address,
            '@',
        );

        if (false === $separator) {
            return;
        }

        $hostname = substr(
            $address,
            $separator + 1,
        );

        if (
            '' === $hostname
            || $this->canReceiveMail($hostname)
        ) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter(
                '{{ hostname }}',
                $hostname,
            )
            ->setCode(DeliverableEmailAddress::NO_MX_RECORD_ERROR)
            ->addViolation();
    }

    /**
     * A host without MX records still accepts mail on its A records (RFC 5321 section 5.1).
     */
    private function canReceiveMail(string $hostname): bool
    {
        $mxHosts = [];

        if (
            getmxrr(
                $hostname,
                $mxHosts,
            )
        ) {
            return true;
        }

        return is_array(gethostbynamel($hostname));
    }
}
