<?php

declare(strict_types=1);

namespace App\Entity\Application;

use DateTime;
use DateTimeInterface;

/**
 * The association year, which runs from July 1st to June 30th.
 *
 * The rollover halfway through the calendar year is the single most repeated rule in the ledger: a date in the second
 * half of a calendar year already belongs to the association year that ends in the next one. It lives here so that
 * every place that needs it states it the same way.
 */
final readonly class AssociationYear
{
    /** The month in which the association year turns over. */
    private const int ROLLOVER_MONTH = 7;

    private function __construct(private int $endYear)
    {
    }

    /**
     * The association year that $moment falls in, named after the calendar year it ends in.
     */
    public static function of(DateTimeInterface $moment): self
    {
        $year = (int) $moment->format('Y');

        if ((int) $moment->format('m') >= self::ROLLOVER_MONTH) {
            ++$year;
        }

        return new self($year);
    }

    /**
     * The calendar year this association year ends in.
     */
    public function endYear(): int
    {
        return $this->endYear;
    }

    /**
     * The first day after this association year: July 1st, at midnight.
     */
    public function endsOn(): DateTime
    {
        return (new DateTime())->setDate($this->endYear, self::ROLLOVER_MONTH, 1)->setTime(0, 0);
    }

    /**
     * September 1st of the calendar year this association year ends in, at midnight.
     *
     * The key policy hangs off this date rather than off the year's own boundary.
     */
    public function septemberFirst(): DateTime
    {
        return (new DateTime())->setDate($this->endYear, 9, 1)->setTime(0, 0);
    }
}
