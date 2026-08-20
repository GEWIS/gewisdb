<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Override;
use Symfony\Component\Form\DataTransformerInterface;

use function mb_strtolower;

/**
 * Lower-cases a submitted e-mail address, so an address is stored and compared in one form however it was typed.
 *
 * @implements DataTransformerInterface<?string, ?string>
 */
class LowercaseTransformer implements DataTransformerInterface
{
    #[Override]
    public function transform(mixed $value): ?string
    {
        return $value;
    }

    #[Override]
    public function reverseTransform(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return mb_strtolower((string) $value);
    }
}
