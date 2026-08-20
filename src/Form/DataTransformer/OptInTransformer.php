<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Override;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Maps a checkbox onto the 'optin'/'optout' strings that the member's Supremum preference is stored as.
 *
 * @implements DataTransformerInterface<?string, ?bool>
 */
class OptInTransformer implements DataTransformerInterface
{
    public const string OPT_IN = 'optin';
    public const string OPT_OUT = 'optout';

    #[Override]
    public function transform(mixed $value): bool
    {
        return self::OPT_IN === $value;
    }

    #[Override]
    public function reverseTransform(mixed $value): string
    {
        return true === $value
            ? self::OPT_IN
            : self::OPT_OUT;
    }
}
