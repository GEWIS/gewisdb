<?php

declare(strict_types=1);

namespace App\Tests\Entity\Database;

use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Member;
use App\Entity\Database\Membership;
use DateTime;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Membership::class)]
class MembershipTest extends TestCase
{
    /**
     * A membership taken out at any point in an association year runs until that year ends, not for twelve months.
     */
    #[DataProvider('startDatesAndTheirExpiration')]
    public function testExpiresWhenTheAssociationYearItStartedInDoes(
        string $startDate,
        string $endDate,
    ): void {
        $membership = new Membership(
            new Member(),
            MembershipTypes::Ordinary,
            new DateTime($startDate),
        );

        self::assertSame(
            $endDate,
            $membership->getEndDate()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function startDatesAndTheirExpiration(): array
    {
        return [
            'joining in August, the month most do' => ['2026-08-20 09:00:00', '2027-07-01 00:00:00'],
            'joining halfway through the year' => ['2026-03-01 09:00:00', '2026-07-01 00:00:00'],
            'joining on the rollover itself' => ['2026-07-01 09:00:00', '2027-07-01 00:00:00'],
        ];
    }

    /**
     * Honorary membership does not expire; the entity says so with a date a century out.
     */
    public function testGivesAnHonoraryMembershipADateItWillNotReach(): void
    {
        $membership = new Membership(
            new Member(),
            MembershipTypes::Honorary,
            new DateTime('2026-08-20'),
        );

        self::assertSame(
            '2127-07-01 00:00:00',
            $membership->getEndDate()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Both ends are dates rather than moments: a membership that starts today started at midnight.
     */
    public function testNormalisesBothDatesToMidnight(): void
    {
        $membership = new Membership(
            new Member(),
            MembershipTypes::Ordinary,
            new DateTime('2026-08-20 13:37:00'),
            new DateTime('2026-09-30 13:37:00'),
        );

        self::assertSame('2026-08-20 00:00:00', $membership->getStartDate()->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-30 00:00:00', $membership->getEndDate()->format('Y-m-d H:i:s'));
    }

    public function testCanBeCutShort(): void
    {
        $membership = $this->membership();

        $membership->setEndDate(new DateTime('2026-12-31'));

        self::assertSame('2026-12-31 00:00:00', $membership->getEndDate()->format('Y-m-d H:i:s'));
    }

    public function testRefusesAnEndDateBeforeItStarted(): void
    {
        $membership = $this->membership();

        $this->expectException(LogicException::class);

        $membership->setEndDate(new DateTime('2026-08-19'));
    }

    /**
     * Extending is a new membership, not an edit: the ledger keeps every term a member has held.
     */
    public function testRefusesToBeExtended(): void
    {
        $membership = $this->membership();

        $this->expectException(LogicException::class);

        $membership->setEndDate(new DateTime('2028-07-01'));
    }

    public function testRefusesANegativeAmountPaid(): void
    {
        $membership = $this->membership();

        $this->expectException(LogicException::class);

        $membership->setPaid(-1);
    }

    public function testIsCurrentBetweenItsStartAndEndDate(): void
    {
        $current = new Membership(
            new Member(),
            MembershipTypes::Ordinary,
            new DateTime('-1 year'),
            new DateTime('+1 year'),
        );
        $expired = new Membership(
            new Member(),
            MembershipTypes::Ordinary,
            new DateTime('-2 years'),
            new DateTime('-1 year'),
        );

        self::assertTrue($current->isCurrent());
        self::assertFalse($expired->isCurrent());
    }

    private function membership(): Membership
    {
        return new Membership(
            new Member(),
            MembershipTypes::Ordinary,
            new DateTime('2026-08-20'),
        );
    }
}
