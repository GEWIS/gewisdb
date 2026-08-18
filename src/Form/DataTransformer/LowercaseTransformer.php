<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Override;
use Symfony\Component\Form\DataTransformerInterface;

use function mb_strtolower;

/**
 * Replaces the laminas-filter `StringToLower` that was applied to submitted e-mail addresses.
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
