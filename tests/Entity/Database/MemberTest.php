<?php

declare(strict_types=1);

namespace App\Tests\Entity\Database;

use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Member;
use App\Entity\Database\Membership;
use App\Entity\Database\RenewalLink;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Member::class)]
class MemberTest extends TestCase
{
    #[DataProvider('names')]
    public function testAssemblesAFullNameWithoutSwallowingTheSpaces(
        string $firstName,
        string $middleName,
        string $lastName,
        string $fullName,
    ): void {
        $member = new Member();
        $member->setFirstName($firstName);
        $member->setMiddleName($middleName);
        $member->setLastName($lastName);

        self::assertSame($fullName, $member->getFullName());
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function names(): array
    {
        return [
            'with a tussenvoegsel' => ['Jan', 'van der', 'Berg', 'Jan van der Berg'],
            'without one' => ['Jan', '', 'Jansen', 'Jan Jansen'],
        ];
    }

    /**
     * The generation is the association year someone joined in, so it turns over on July 1st like everything else.
     */
    #[DataProvider('firstMembershipsAndGenerations')]
    public function testTakesTheGenerationFromTheFirstMembership(
        string $startDate,
        int $generation,
    ): void {
        $member = new Member();
        $member->addMembership(
            new Membership($member, MembershipTypes::Ordinary, new DateTime($startDate)),
        );

        self::assertSame($generation, $member->getGeneration());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function firstMembershipsAndGenerations(): array
    {
        return [
            'joined in August' => ['2026-08-20', 2026],
            'joined in the second half of the association year' => ['2027-02-01', 2026],
            'joined on the rollover' => ['2026-07-01', 2026],
        ];
    }

    public function testHasNoGenerationBeforeTheFirstMembership(): void
    {
        self::assertSame(0, new Member()->getGeneration());
    }

    /**
     * Membership ends on the latest end date of the memberships that are a membership: a graduate is registered but
     * is not a member, so a graduate term does not extend it.
     */
    public function testEndsMembershipOnTheLastFormalMembership(): void
    {
        $member = $this->memberWithHistory();

        self::assertSame('2027-07-01', $member->getMembershipEndDate()?->format('Y-m-d'));
        self::assertSame('2028-07-01', $member->getExpiration()->format('Y-m-d'));
    }

    /**
     * The sentinel is what the interface shows, so it has to be a date rather than nothing at all.
     */
    public function testFallsBackOnASentinelForSomeoneWhoWasNeverAMember(): void
    {
        $member = new Member();
        $member->addMembership(
            new Membership(
                $member,
                MembershipTypes::Graduate,
                new DateTime('2027-08-01'),
                new DateTime('2028-07-01'),
            ),
        );

        self::assertNull($member->getMembershipEndDate());
        self::assertSame('0001-01-01', $member->getMembershipEndsOn()->format('Y-m-d'));
    }

    public function testKnowsWhichMembershipIsRunningRightNow(): void
    {
        $member = new Member();
        $expired = new Membership(
            $member,
            MembershipTypes::Ordinary,
            new DateTime('-3 years'),
            new DateTime('-2 years'),
        );
        $current = new Membership(
            $member,
            MembershipTypes::Ordinary,
            new DateTime('-1 month'),
            new DateTime('+11 months'),
        );
        $member->addMembership($expired);
        $member->addMembership($current);

        self::assertSame($current, $member->getCurrentMembership());
        self::assertSame($current, $member->getCurrentOrLastMembership());
        self::assertSame($current, $member->getLastMembership());
    }

    /**
     * Once a membership has run out there is still a member to show a page for, and it is the last one that says
     * what they were.
     */
    public function testFallsBackOnTheLastMembershipOnceItHasRunOut(): void
    {
        $member = new Member();
        $expired = new Membership(
            $member,
            MembershipTypes::Ordinary,
            new DateTime('-3 years'),
            new DateTime('-2 years'),
        );
        $member->addMembership($expired);

        self::assertNull($member->getCurrentMembership());
        self::assertSame($expired, $member->getCurrentOrLastMembership());
    }

    public function testHasNoMembershipAtAllBeforeOneIsAdded(): void
    {
        $member = new Member();

        self::assertNull($member->getCurrentMembership());
        self::assertNull($member->getCurrentOrLastMembership());
        self::assertNull($member->getLastMembership());
    }

    /**
     * A second renewal e-mail is not sent while a link that still works is outstanding.
     */
    public function testHasAnActiveRenewalLinkOnlyWhileOneStillWorks(): void
    {
        $member = $this->memberWithHistory();

        self::assertFalse($member->hasActiveRenewalLink());

        $member->getRenewalLinks()->add(
            new RenewalLink($member, new DateTime('+2 years')),
        );

        self::assertTrue($member->hasActiveRenewalLink());
    }

    /**
     * An ordinary membership that ran until 2027, followed by a graduate registration until 2028.
     */
    private function memberWithHistory(): Member
    {
        $member = new Member();
        $member->addMembership(
            new Membership(
                $member,
                MembershipTypes::Ordinary,
                new DateTime('2026-08-20'),
            ),
        );
        $member->addMembership(
            new Membership(
                $member,
                MembershipTypes::Graduate,
                new DateTime('2027-08-01'),
                new DateTime('2028-07-01'),
            ),
        );

        return $member;
    }
}
