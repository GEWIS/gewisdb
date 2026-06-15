<?php

declare(strict_types=1);

namespace Application\Extensions\Types;

// Source - https://stackoverflow.com/a/15085566
// Posted by Ocramius, modified by community. See post 'Timeline' for change history
// Retrieved 2026-06-15, License - CC BY-SA 4.0, relicensed under GPL-3.0 on 2026-06-15

use DateTime;

class StringableDateTime extends DateTime
{
    public function __toString(): string
    {
        return $this->format('U');
    }

    public function toDateTime(): DateTime
    {
        $val = new DateTime($this->format(DateTime::ATOM));
        $val->setTimezone($this->getTimezone());

        return $val;
    }

    public static function fromDateTime(DateTime $dateTime): self
    {
        $val = new self($dateTime->format(DateTime::ATOM));
        $val->setTimezone($dateTime->getTimezone());

        return $val;
    }
}
