<?php

declare(strict_types=1);

namespace App\Tests\Entity\Database;

use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Member;
use App\Entity\Database\Membership;
use App\Entity\Database\RenewalLink;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function str_contains;
use function strlen;

#[CoversClass(RenewalLink::class)]
class RenewalLinkTest extends TestCase
{
    /**
     * The token is the whole credential: it is what `/renew/{token}` is looked up by, and the only thing standing
     * between an anonymous visitor and someone else's renewal.
     */
    public function testCarriesATokenThatIsUnguessableAndFitsInAUrl(): void
    {
        $first = $this->link();
        $second = $this->link();

        self::assertNotSame($first->getToken(), $second->getToken());
        self::assertGreaterThanOrEqual(128, strlen($first->getToken()));
        self::assertFalse(str_contains($first->getToken(), '/'));
    }

    public function testStartsOutUnused(): void
    {
        $link = $this->link();

        self::assertFalse($link->isUsed());

        $link->setUsed(true);

        self::assertTrue($link->isUsed());
    }

    /**
     * The link records what it would change, and there is nothing to renew towards a date that is already reached.
     */
    public function testRecordsTheExpirationItWouldMoveAndRefusesOneThatIsNotLater(): void
    {
        $member = $this->member('2026-07-01');
        $link = new RenewalLink($member, new DateTime('2027-07-01'));

        self::assertSame('2026-07-01', $link->getCurrentExpiration()->format('Y-m-d'));
        self::assertSame('2027-07-01', $link->getNewExpiration()->format('Y-m-d'));
        self::assertSame($member, $link->getMember());

        $this->expectException(InvalidArgumentException::class);

        new RenewalLink($member, new DateTime('2026-07-01'));
    }

    /**
     * A renewal link outlives the membership by 30 days, so someone whose account has just locked can still use the
     * link they were sent.
     */
    #[DataProvider('expirationsAndWhetherTheLinkStillWorks')]
    public function testKeepsWorkingForThirtyDaysAfterTheMembershipRanOut(
        string $currentExpiration,
        bool $expired,
    ): void {
        $link = new RenewalLink(
            $this->member($currentExpiration),
            new DateTime('+5 years'),
        );

        self::assertSame($expired, $link->linkExpired());
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function expirationsAndWhetherTheLinkStillWorks(): array
    {
        return [
            'membership has not run out yet' => ['+2 months', false],
            'ran out yesterday' => ['-1 day', false],
            'ran out within the grace period' => ['-10 days', false],
            'ran out well past it' => ['-40 days', true],
        ];
    }

    private function link(): RenewalLink
    {
        return new RenewalLink(
            $this->member('2026-07-01'),
            new DateTime('2027-07-01'),
        );
    }

    /**
     * A member whose membership ends on $expiration, which is what the link reads.
     */
    private function member(string $expiration): Member
    {
        $member = new Member();
        $member->addMembership(
            new Membership(
                $member,
                MembershipTypes::Ordinary,
                new DateTime('-2 years'),
                new DateTime($expiration),
            ),
        );

        return $member;
    }
}
