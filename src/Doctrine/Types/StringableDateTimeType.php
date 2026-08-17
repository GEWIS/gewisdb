<?php

declare(strict_types=1);

namespace App\Doctrine\Types;

// Source - https://stackoverflow.com/a/15085566
// Posted by Ocramius, modified by community. See post 'Timeline' for change history
// Retrieved 2026-06-15, License - CC BY-SA 4.0, relicensed under GPL-3.0 on 2026-06-15

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;
use Override;

/**
 * Registered as `stringable_datetime` in config/packages/doctrine.yaml.
 *
 * Two methods the Laminas version carried are gone in DBAL 4 and have been dropped rather than reimplemented:
 *
 * - `getName()`. DBAL 4 removed self-naming from Type entirely; a type is known by the key it is registered under,
 *   which is now the one in doctrine.yaml. Keeping the method would not fail loudly, it would simply never be called.
 * - `requiresSQLCommentHint()`. DBAL 4 dropped the doctrine-type comment hints it existed to trigger, along with the
 *   schema-introspection behaviour that read them back.
 *
 * Neither removal changes the generated DDL: the column is still whatever DateTimeType declares.
 */
class StringableDateTimeType extends DateTimeType
{
    /**
     * {@inheritDoc}
     *
     * Narrowing the parent's `?DateTime` to `?StringableDateTime` is safe — StringableDateTime extends DateTime — and
     * is what lets entities type their properties as the stringable variant.
     */
    #[Override]
    public function convertToPHPValue(
        mixed $value,
        AbstractPlatform $platform,
    ): ?StringableDateTime {
        $dateTime = parent::convertToPHPValue($value, $platform);

        if (null === $dateTime) {
            return null;
        }

        $val = new StringableDateTime('@' . $dateTime->format('U'));
        $val->setTimezone($dateTime->getTimezone());

        return $val;
    }
}
