<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine\DBAL\Types;

// Source - https://stackoverflow.com/a/15085566
// Posted by Ocramius, modified by community. See post 'Timeline' for change history
// Retrieved 2026-06-15, License - CC BY-SA 4.0, relicensed under GPL-3.0 on 2026-06-15

use Application\Extensions\Types\StringableDateTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;
use Override;

class StringableDateTimeType extends DateTimeType
{
    /**
     * {@inheritDoc}
     *
     * @template T
     *
     * @param T $value
     *
     * @return (T is null ? null : StringableDateTime)
     */
    #[Override]
    public function convertToPHPValue(
        $value,
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

    #[Override]
    public function requiresSQLCommentHint(AbstractPlatform $platform): true
    {
        return true;
    }

    #[Override]
    public function getName(): string
    {
        return 'stringable_datetime';
    }
}
