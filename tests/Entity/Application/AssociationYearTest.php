<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application;

use App\Entity\Application\AssociationYear;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssociationYear::class)]
class AssociationYearTest extends TestCase
{
    /**
     * The rollover is the rule the rest of the ledger leans on, so both sides of July 1st are pinned down here. A
     * year is named after the calendar year it starts in, as it is in GEWISWEB.
     */
    #[DataProvider('momentsAndTheYearTheyBelongTo')]
    public function testNamesTheAssociationYearAfterTheCalendarYearItStartsIn(
        string $moment,
        int $firstYear,
    ): void {
        self::assertSame(
            $firstYear,
            AssociationYear::fromDate(new DateTime($moment))->getYear(),
        );
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function momentsAndTheYearTheyBelongTo(): array
    {
        return [
            'first day of the calendar year' => ['2026-01-01 00:00:00', 2025],
            'last moment before the rollover' => ['2026-06-30 23:59:59', 2025],
            'the rollover itself' => ['2026-07-01 00:00:00', 2026],
            'the month members join in' => ['2026-08-20 12:00:00', 2026],
            'last day of the calendar year' => ['2026-12-31 23:59:59', 2026],
        ];
    }

    public function testTakesAnyDateTimeInterface(): void
    {
        self::assertSame(
            2026,
            AssociationYear::fromDate(new DateTimeImmutable('2026-09-01'))->getYear(),
        );
    }

    public function testCanBeNamedDirectly(): void
    {
        self::assertSame(2026, AssociationYear::fromYear(2026)->getYear());
        self::assertSame('2026-2027', AssociationYear::fromYear(2026)->getYearString());
    }

    public function testRunsFromJulyFirstToTheLastMomentOfJuneThirtieth(): void
    {
        $year = AssociationYear::fromDate(new DateTime('2026-08-20 12:34:56'));

        self::assertSame('2026-07-01 00:00:00.000000', $year->getStartDate()->format('Y-m-d H:i:s.u'));
        self::assertSame('2027-06-30 23:59:59.999999', $year->getEndDate()->format('Y-m-d H:i:s.u'));
    }

    /**
     * The exclusive end, which is what a stored date is compared against: it is the next year's first day, so it
     * cannot fall in the gap June 30th 23:59:59.999999 leaves.
     */
    public function testEndsOnTheDayTheNextYearBegins(): void
    {
        $year = AssociationYear::fromDate(new DateTime('2026-08-20'));

        self::assertSame('2027-07-01 00:00:00', $year->endsOn()->format('Y-m-d H:i:s'));
        self::assertEquals(AssociationYear::fromYear(2027)->getStartDate(), $year->endsOn());
        self::assertGreaterThan($year->getEndDate(), $year->endsOn());
    }

    public function testSeptemberFirstFollowsTheYearItEndsIn(): void
    {
        $year = AssociationYear::fromDate(new DateTime('2026-02-01'));

        self::assertSame(2025, $year->getYear());
        self::assertSame('2026-09-01 00:00:00', $year->septemberFirst()->format('Y-m-d H:i:s'));
    }

    /**
     * Every date comes out fresh, because callers do move them: a membership takes its expiry from `endsOn()` and an
     * honorary one then pushes it a century out.
     */
    public function testHandsOutADateTheCallerMayMove(): void
    {
        $year = AssociationYear::fromYear(2026);

        $year->endsOn()->modify('+100 years');

        self::assertSame('2027-07-01 00:00:00', $year->endsOn()->format('Y-m-d H:i:s'));
    }
}
