<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Checker;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision;
use App\Service\Checker\Checker;
use App\Tests\Support\LedgerBuilder;
use App\ViewModel\Checker\Error;
use App\ViewModel\Checker\Error\AnnulmentOfAnnulment;
use App\ViewModel\Checker\Error\AnnulmentOfLaterDecision;
use App\ViewModel\Checker\Error\KeyGrantedInThePast;
use App\ViewModel\Checker\Error\KeyGrantedPastBoundary;
use App\ViewModel\Checker\Error\KeyWithdrawnPastOriginalGranting;
use App\ViewModel\Checker\Error\MemberExpiredButStillInOrgan;
use App\ViewModel\Checker\Error\MemberHasRoleButNotInOrgan;
use App\ViewModel\Checker\Error\MemberInactiveInOrganWithoutInactiveMembers;
use App\ViewModel\Checker\Error\OrganMeetingType;
use App\ViewModel\Checker\Error\OrganTooSmall;
use App\ViewModel\Checker\Error\OrganWithoutChair;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function array_filter;
use function array_values;

/**
 * The consistency checks from the Articles of Association and the Internal Regulations, each against a ledger built
 * to break exactly one of them.
 *
 * The checks run over everything up to the meeting they are given, the seed included, so every assertion is about
 * the decisions this test made rather than about how many errors came back.
 */
#[CoversClass(Checker::class)]
class CheckerTest extends KernelTestCase
{
    private Checker $checker;
    private LedgerBuilder $build;

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->checker = self::getContainer()->get(Checker::class);
        $this->build = new LedgerBuilder(self::getContainer()->get(EntityManagerInterface::class));
    }

    /**
     * A chair's meeting may found nothing at all, and a members' meeting only the organs the regulations name.
     */
    public function testReportsAnOrganFoundedByAMeetingThatMayNotFoundIt(): void
    {
        $meeting = $this->build->meeting(MeetingTypes::ALV);
        $committee = $this->build->foundOrgan($meeting, 'WTC');

        self::assertCount(
            1,
            $this->errorsAbout($this->checker->checkOrganFoundationMeetingType($meeting), $committee, OrganMeetingType::class),
        );
    }

    public function testAcceptsAnOrganFoundedByTheMeetingThatMay(): void
    {
        $meeting = $this->build->meeting(MeetingTypes::BV);
        $committee = $this->build->foundOrgan($meeting, 'GTC');

        self::assertSame(
            [],
            $this->errorsAbout($this->checker->checkOrganFoundationMeetingType($meeting), $committee, OrganMeetingType::class),
        );
    }

    /**
     * An organ that nobody is in is an organ that should have been abolished.
     */
    public function testReportsAnOrganWithNobodyInIt(): void
    {
        $meeting = $this->build->meeting();
        $committee = $this->build->foundOrgan($meeting, 'ETC');

        self::assertCount(
            1,
            $this->errorsAbout($this->checker->checkOrganComposition($meeting), $committee, OrganTooSmall::class),
        );
    }

    public function testReportsAnOrganWithoutAChair(): void
    {
        $meeting = $this->build->meeting();
        $committee = $this->build->foundOrgan($meeting, 'CTC');
        $this->build->install($meeting, $committee, $this->build->member(), InstallationFunctions::Member);

        self::assertCount(
            1,
            $this->errorsAbout($this->checker->checkOrganComposition($meeting), $committee, OrganWithoutChair::class),
        );
    }

    public function testAcceptsAnOrganWithAMemberAndAChair(): void
    {
        $meeting = $this->build->meeting();
        $committee = $this->build->foundOrgan($meeting, 'HTC');
        $this->build->install(
            $meeting,
            $committee,
            $this->build->member(),
            InstallationFunctions::Member,
            InstallationFunctions::Chair,
        );

        self::assertSame([], $this->errorsAbout($this->checker->checkOrganComposition($meeting), $committee));
    }

    /**
     * Only fraternities keep members who hold no function (HR art. 13); everywhere else they are discharged.
     */
    public function testReportsAnInactiveMemberInAnOrganThatHasNone(): void
    {
        $meeting = $this->build->meeting();
        $committee = $this->build->foundOrgan($meeting, 'NTC');
        $inactive = $this->build->install(
            $meeting,
            $committee,
            $this->build->member(),
            InstallationFunctions::InactiveMember,
        );

        self::assertCount(
            1,
            $this->errorsAbout(
                $this->checker->checkOrganComposition($meeting),
                $inactive,
                MemberInactiveInOrganWithoutInactiveMembers::class,
            ),
        );
    }

    public function testAcceptsAnInactiveMemberInAFraternity(): void
    {
        $meeting = $this->build->meeting();
        $fraternity = $this->build->foundOrgan($meeting, 'FRA', 'Testdispuut', OrganTypes::Fraternity);
        $inactive = $this->build->install(
            $meeting,
            $fraternity,
            $this->build->member(),
            InstallationFunctions::InactiveMember,
        );

        self::assertSame(
            [],
            $this->errorsAbout(
                $this->checker->checkOrganComposition($meeting),
                $inactive,
                MemberInactiveInOrganWithoutInactiveMembers::class,
            ),
        );
    }

    /**
     * Holding a function in a body means being in it: a chair who is not a member of their own committee is a gap in
     * the record rather than an arrangement.
     */
    public function testReportsSomeoneHoldingAFunctionWithoutBeingAMemberOfTheOrgan(): void
    {
        $meeting = $this->build->meeting();
        $committee = $this->build->foundOrgan($meeting, 'RTC');
        $chair = $this->build->install($meeting, $committee, $this->build->member(), InstallationFunctions::Chair);

        self::assertCount(
            1,
            $this->errorsAbout(
                $this->checker->checkMembersHaveRolesButInactiveOrNotInOrgan($meeting),
                $chair,
                MemberHasRoleButNotInOrgan::class,
            ),
        );
    }

    public function testReportsSomeoneStillInAnOrganAfterTheirMembershipRanOut(): void
    {
        $meeting = $this->build->meeting(date: '2027-01-15');
        $committee = $this->build->foundOrgan($meeting, 'XTC');
        $expired = $this->build->install(
            $meeting,
            $committee,
            $this->build->member(
                MembershipTypes::Ordinary,
                '2024-08-01',
                '2025-07-01',
            ),
            InstallationFunctions::Member,
        );

        self::assertCount(
            1,
            $this->errorsAbout(
                $this->checker->checkMembersExpiredButStillInOrgan($meeting),
                $expired,
                MemberExpiredButStillInOrgan::class,
            ),
        );
    }

    /**
     * The Key Policy stops every key code at September 1st of the next association year, whatever the meeting says.
     */
    public function testReportsAKeyCodeGrantedPastTheBoundary(): void
    {
        $meeting = $this->build->meeting(date: '2026-08-20');
        $granting = $this->build->grantKey($meeting, $this->build->member(), '2027-09-02');

        self::assertCount(
            1,
            $this->errorsAbout($this->checker->checkKeyGrantingDuration($meeting), $granting, KeyGrantedPastBoundary::class),
        );
    }

    public function testAcceptsAKeyCodeThatStopsOnTheBoundary(): void
    {
        $meeting = $this->build->meeting(date: '2026-08-20');
        $granting = $this->build->grantKey($meeting, $this->build->member(), '2027-09-01');

        self::assertSame([], $this->errorsAbout($this->checker->checkKeyGrantingDuration($meeting), $granting));
    }

    public function testReportsAKeyCodeThatHadAlreadyExpiredWhenItWasGranted(): void
    {
        $meeting = $this->build->meeting(date: '2026-08-20');
        $granting = $this->build->grantKey($meeting, $this->build->member(), '2026-08-01');

        self::assertCount(
            1,
            $this->errorsAbout($this->checker->checkKeyGrantingDuration($meeting), $granting, KeyGrantedInThePast::class),
        );
    }

    public function testReportsAKeyCodeWithdrawnAfterItHadAlreadyExpired(): void
    {
        $meeting = $this->build->meeting(date: '2026-08-20');
        $granting = $this->build->grantKey($meeting, $this->build->member(), '2026-12-01');
        $withdrawal = $this->build->withdrawKey($this->build->meeting(date: '2027-01-15'), $granting, '2027-01-15');

        self::assertCount(
            1,
            $this->errorsAbout(
                $this->checker->checkKeyWithdrawalTime($withdrawal->getDecision()->getMeeting()),
                $withdrawal,
                KeyWithdrawnPastOriginalGranting::class,
            ),
        );
    }

    /**
     * An annulment has no effects of its own to take back, so annulling one means nothing.
     */
    public function testReportsAnAnnulmentOfAnAnnulment(): void
    {
        $foundation = $this->build->foundOrgan($this->build->meeting(), 'ATA');
        $first = $this->build->annul($this->build->meeting(date: '2027-01-15'), $foundation->getDecision());
        $meeting = $this->build->meeting(date: '2027-06-01');
        $second = $this->build->annul($meeting, $first->getDecision());

        self::assertCount(
            1,
            $this->errorsAbout($this->checker->checkAnnulments($meeting), $second, AnnulmentOfAnnulment::class),
        );
    }

    /**
     * The ledger cannot be rewritten from the past: a decision is annulled by a later one, never by an earlier one.
     */
    public function testReportsAnAnnulmentOfALaterDecision(): void
    {
        $meeting = $this->build->meeting(date: '2026-08-20');
        $later = $this->build->foundOrgan($this->build->meeting(date: '2027-06-01'), 'LTR');
        $annulment = $this->build->annul($meeting, $later->getDecision());

        self::assertCount(
            1,
            $this->errorsAbout($this->checker->checkAnnulments($meeting), $annulment, AnnulmentOfLaterDecision::class),
        );
    }

    /**
     * The errors this test's own decisions produced, optionally of one kind.
     *
     * @param Error<SubDecision>[] $errors
     * @param class-string|null    $type The kind of error to keep, or every kind.
     *
     * @return Error<SubDecision>[]
     */
    private function errorsAbout(
        array $errors,
        SubDecision $subDecision,
        ?string $type = null,
    ): array {
        return array_values(array_filter(
            $errors,
            static function (Error $error) use ($subDecision, $type): bool {
                if (
                    null !== $type
                    && $error::class !== $type
                ) {
                    return false;
                }

                return $error->getSubDecision()->getHash() === $subDecision->getHash();
            },
        ));
    }
}
