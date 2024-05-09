<?php

declare(strict_types=1);

namespace Database\Model\Trait;

use DateTime;
use IntlDateFormatter;

use function date_default_timezone_get;

trait FormattableDateTrait
{
    /**
     * Format a `DateTime` in a specified locale.
     *
     * With {@see IntlDateFormatter::LONG} the date will be formatted using the day of the month, full month, and
     * 4-digit year. For example, for
     */
    protected function formatDate(
        DateTime $date,
        string $locale = 'nl_NL',
    ): string {
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            date_default_timezone_get(),
        );

        return $formatter->format($date);
    }
}
