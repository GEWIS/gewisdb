<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use BackedEnum;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function sprintf;

/**
 * Maps a hidden or select field carrying a backed enum value onto the enum itself.
 *
 * Rejecting an empty or unknown value here rather than letting it through as `null` keeps the field from writing
 * `null` into a non-nullable enum property while the form is being mapped, which happens before validation runs.
 *
 * @template T of BackedEnum
 *
 * @implements DataTransformerInterface<T, string>
 */
final readonly class StringToEnumTransformer implements DataTransformerInterface
{
    /** @param class-string<T> $enum */
    public function __construct(private string $enum)
    {
    }

    #[Override]
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        return (string) $value->value;
    }

    /** @return T */
    #[Override]
    public function reverseTransform(mixed $value): BackedEnum
    {
        if (
            null === $value
            || '' === $value
        ) {
            throw new TransformationFailedException(sprintf('No value given for %s.', $this->enum));
        }

        $case = $this->enum::tryFrom($value);

        if (null === $case) {
            throw new TransformationFailedException(sprintf('"%s" is not a valid %s.', $value, $this->enum));
        }

        return $case;
    }
}
