<?php

declare(strict_types=1);

namespace App\Entity\Application;

use DateInterval;
use DateTime;
use DateTimeInterface;

use function sprintf;

/**
 * The association year, which runs from July 1st to June 30th.
 *
 * GEWISWEB's `App\Entity\Decision\AssociationYear`, so a year is named the same thing in both applications: after the
 * calendar year it *starts* in, the first half of "2026-2027". The rollover halfway through the calendar year is the
 * single most repeated rule in the ledger, and it lives here so that every place that needs it states it the same way.
 *
 * What GEWISWEB does not have is added on top: the exclusive end, which is what a date column should be compared
 * against, and the September 1st the key policy hangs off.
 */
final readonly class AssociationYear
{
    /**
     * A GEWIS association year starts 01-07.
     */
    public const int ASSOCIATION_YEAR_START_MONTH = 7;
    public const int ASSOCIATION_YEAR_START_DAY = 1;

    /**
     * @param int $firstYear the first calendar year of the association year
     */
    private function __construct(private int $firstYear)
    {
    }

    /**
     * @param int $year the first calendar year of the association year
     */
    public static function fromYear(int $year): self
    {
        return new self($year);
    }

    /**
     * The association year $dateTime falls in.
     *
     * Takes any DateTimeInterface rather than only a DateTime, because nothing here needs to modify it.
     */
    public static function fromDate(DateTimeInterface $dateTime): self
    {
        $year = (int) $dateTime->format('Y');

        // The association year starts on the first of the month, so the month alone decides which one a date is in.
        if ((int) $dateTime->format('n') < self::ASSOCIATION_YEAR_START_MONTH) {
            --$year;
        }

        return new self($year);
    }

    /**
     * The first calendar year of this association year.
     */
    public function getYear(): int
    {
        return $this->firstYear;
    }

    /**
     * The association year as it is written down and spoken of: "2026-2027".
     */
    public function getYearString(): string
    {
        return sprintf(
            '%4d-%4d',
            $this->firstYear,
            $this->firstYear + 1,
        );
    }

    /**
     * The first day of this association year: July 1st, at midnight.
     */
    public function getStartDate(): DateTime
    {
        return new DateTime()->setDate(
            $this->firstYear,
            self::ASSOCIATION_YEAR_START_MONTH,
            self::ASSOCIATION_YEAR_START_DAY,
        )->setTime(
            0,
            0,
        );
    }

    /**
     * The last moment of this association year: June 30th, a microsecond before midnight.
     *
     * Prefer {@see AssociationYear::endsOn()} for anything a stored date is compared against: this one only holds if
     * the other side of the comparison carries microseconds too, which a `date` column does not.
     */
    public function getEndDate(): DateTime
    {
        return $this->endsOn()->sub(new DateInterval('P1D'))->setTime(
            23,
            59,
            59,
            999999,
        );
    }

    /**
     * The first day after this association year: July 1st, at midnight.
     *
     * The exclusive end, and the same instant as the start of the year that follows, which is what makes it safe to
     * compare a date against without knowing how precisely that date was stored.
     */
    public function endsOn(): DateTime
    {
        return self::fromYear($this->firstYear + 1)->getStartDate();
    }

    /**
     * September 1st of the calendar year this association year ends in, at midnight.
     *
     * The key policy hangs off this date rather than off the year's own boundary.
     */
    public function septemberFirst(): DateTime
    {
        return new DateTime()->setDate(
            $this->firstYear + 1,
            9,
            1,
        )->setTime(
            0,
            0,
        );
    }
}
