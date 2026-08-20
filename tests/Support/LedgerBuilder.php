<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\Meeting;
use App\Entity\Database\Member;
use App\Entity\Database\Membership;
use App\Entity\Database\SubDecision\Abrogation;
use App\Entity\Database\SubDecision\Annulment;
use App\Entity\Database\SubDecision\Board\Installation as BoardInstallation;
use App\Entity\Database\SubDecision\Board\Release as BoardRelease;
use App\Entity\Database\SubDecision\Discharge;
use App\Entity\Database\SubDecision\Foundation;
use App\Entity\Database\SubDecision\Installation;
use App\Entity\Database\SubDecision\Key\Granting;
use App\Entity\Database\SubDecision\Key\Withdrawal;
use App\Entity\Database\SubDecision\Reappointment;
use App\Entity\Database\Enums\BoardFunctions;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

use function spl_object_id;
use function sprintf;

/**
 * Writes the meeting → decision → subdecision graphs an integration test needs into the ledger.
 *
 * The seed is there to be read, not to be built on: a test that leans on which member happens to be in the fixtures
 * is a test that changes meaning when the fixtures do. So everything a test asserts about is made here, in meetings
 * of its own numbered well past the seed's, and every builder flushes — the projection listeners run on flush, which
 * is what makes the ReportDB side observable at all.
 *
 * Each decision gets its own point within its meeting, since the two together are half of a decision's identity.
 * Meetings are board meetings unless a test says otherwise, because that is the meeting type that may found an
 * ordinary committee — data built here should not break the regulations by accident.
 */
final class LedgerBuilder
{
    /** @var int<0, max> Well past what the fixtures use, so nothing built here can collide with the seed. */
    private int $meetingNumber = 9000;

    private int $memberCounter = 0;

    /** @var array<int, int> The next free decision point per meeting. */
    private array $points = [];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function meeting(
        MeetingTypes $type = MeetingTypes::BV,
        string $date = '2026-08-20',
    ): Meeting {
        $meeting = new Meeting();
        $meeting->setType($type);
        $meeting->setNumber(++$this->meetingNumber);
        $meeting->setDate(new DateTime($date));

        $this->entityManager->persist($meeting);
        $this->entityManager->flush();

        return $meeting;
    }

    /**
     * A member with one ordinary membership, current unless another period is asked for.
     */
    public function member(
        MembershipTypes $type = MembershipTypes::Ordinary,
        string $membershipStart = '-1 month',
        ?string $membershipEnd = '+11 months',
    ): Member {
        $number = ++$this->memberCounter;

        $member = new Member();
        $member->setInitials('T.');
        $member->setFirstName('Test');
        $member->setMiddleName('');
        $member->setLastName(sprintf('Testlid %d', $number));
        $member->setEmail(sprintf('testlid-%d@example.org', $number));
        $member->setBirth(new DateTime('2000-01-01'));
        $member->setChangedOn(new DateTime());

        $member->addMembership(
            new Membership(
                $member,
                $type,
                new DateTime($membershipStart),
                null === $membershipEnd ? null : new DateTime($membershipEnd),
            ),
        );

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    public function foundOrgan(
        Meeting $meeting,
        string $abbreviation = 'TC',
        string $name = 'Taartcommissie',
        OrganTypes $type = OrganTypes::Committee,
    ): Foundation {
        $foundation = new Foundation();
        $foundation->setAbbr($abbreviation);
        $foundation->setName($name);
        $foundation->setOrganType($type);
        $foundation->setSequence(1);
        $foundation->setDecision($this->decision($meeting));

        return $this->persist($foundation);
    }

    /**
     * Install someone in an organ. Several functions land in one decision, as they do in a real one.
     *
     * @return Installation the installation for the first function given
     */
    public function install(
        Meeting $meeting,
        Foundation $foundation,
        Member $member,
        InstallationFunctions ...$functions,
    ): Installation {
        $decision = $this->decision($meeting);
        $installations = [];
        $sequence = 1;

        foreach ($functions as $function) {
            $installation = new Installation();
            $installation->setFoundation($foundation);
            $installation->setMember($member);
            $installation->setFunction($function);
            $installation->setSequence($sequence++);
            $installation->setDecision($decision);

            $this->entityManager->persist($installation);

            $installations[] = $installation;
        }

        $this->entityManager->flush();

        return $installations[0];
    }

    public function discharge(
        Meeting $meeting,
        Installation $installation,
    ): Discharge {
        $discharge = new Discharge();
        $discharge->setInstallation($installation);
        $discharge->setSequence(1);
        $discharge->setDecision($this->decision($meeting));

        return $this->persist($discharge);
    }

    public function reappoint(
        Meeting $meeting,
        Installation $installation,
    ): Reappointment {
        $reappointment = new Reappointment();
        $reappointment->setInstallation($installation);
        $reappointment->setSequence(1);
        $reappointment->setDecision($this->decision($meeting));

        return $this->persist($reappointment);
    }

    public function abrogate(
        Meeting $meeting,
        Foundation $foundation,
    ): Abrogation {
        $abrogation = new Abrogation();
        $abrogation->setFoundation($foundation);
        $abrogation->setSequence(1);
        $abrogation->setDecision($this->decision($meeting));

        return $this->persist($abrogation);
    }

    public function grantKey(
        Meeting $meeting,
        Member $member,
        string $until = '+2 months',
    ): Granting {
        $granting = new Granting();
        $granting->setMember($member);
        $granting->setUntil(new DateTime($until));
        $granting->setSequence(1);
        $granting->setDecision($this->decision($meeting));

        return $this->persist($granting);
    }

    public function withdrawKey(
        Meeting $meeting,
        Granting $granting,
        string $withdrawnOn = '+1 month',
    ): Withdrawal {
        $withdrawal = new Withdrawal();
        $withdrawal->setGranting($granting);
        $withdrawal->setWithdrawnOn(new DateTime($withdrawnOn));
        $withdrawal->setSequence(1);
        $withdrawal->setDecision($this->decision($meeting));

        return $this->persist($withdrawal);
    }

    public function installBoard(
        Meeting $meeting,
        Member $member,
        BoardFunctions $function = BoardFunctions::Chair,
        string $date = '2026-09-01',
    ): BoardInstallation {
        $installation = new BoardInstallation();
        $installation->setMember($member);
        $installation->setFunction($function);
        $installation->setDate(new DateTime($date));
        $installation->setSequence(1);
        $installation->setDecision($this->decision($meeting));

        return $this->persist($installation);
    }

    public function releaseBoard(
        Meeting $meeting,
        BoardInstallation $installation,
        string $date = '2027-09-01',
    ): BoardRelease {
        $release = new BoardRelease();
        $release->setInstallation($installation);
        $release->setDate(new DateTime($date));
        $release->setSequence(1);
        $release->setDecision($this->decision($meeting));

        return $this->persist($release);
    }

    public function annul(
        Meeting $meeting,
        Decision $target,
    ): Annulment {
        $annulment = new Annulment();
        $annulment->setTarget($target);
        $annulment->setSequence(1);
        $annulment->setDecision($this->decision($meeting));

        return $this->persist($annulment);
    }

    public function decision(Meeting $meeting): Decision
    {
        $key = spl_object_id($meeting);
        $point = $this->points[$key] ?? 1;
        $this->points[$key] = $point + 1;

        $decision = new Decision();
        $decision->setMeeting($meeting);
        $decision->setPoint($point);
        $decision->setNumber(1);

        $this->entityManager->persist($decision);

        return $decision;
    }

    /**
     * @template T of object
     *
     * @param T $subDecision
     *
     * @return T
     */
    private function persist(object $subDecision): object
    {
        $this->entityManager->persist($subDecision);
        $this->entityManager->flush();

        return $subDecision;
    }
}
