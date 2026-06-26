<?php

declare(strict_types=1);

namespace DatabaseTest\Seeder;

use Application\Model\Enums\MeetingTypes;
use Application\Model\Enums\OrganTypes;
use Database\Model\Decision;
use Database\Model\Enums\InstallationFunctions;
use Database\Model\Meeting;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Discharge;
use Database\Model\SubDecision\Foundation;
use Database\Model\SubDecision\Installation;
use Database\Model\SubDecision\OrganRegulation;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * Founds one organ and installs the "attention" members that must count as active organ members {@see MemberFixture}.
 *
 * The meetings live here (not in MeetingFixture) because a Decision's identity is derived from its Meeting; Doctrine
 * can only resolve that derived identity when the meeting is created and flushed in the same fixture as the decisions
 * that reference it.
 */
class DecisionFixture extends AbstractFixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Two board meetings continuing after MeetingFixture's BV 1. The founding meeting sits 3 years ago: after every
        // installed member joined (the most recent joined ~4 years ago) yet before today, so the installations count as
        // active now and fall inside each member's membership. The discharge meeting is 10 days ago: after the
        // misclassified member's membership already expired (30 days ago) but before today.
        $foundingMeeting = new Meeting();
        $foundingMeeting->setType(MeetingTypes::BV);
        $foundingMeeting->setNumber(2);
        $foundingMeeting->setDate((new DateTime())->modify('-3 years'));
        $manager->persist($foundingMeeting);

        $dischargeMeeting = new Meeting();
        $dischargeMeeting->setType(MeetingTypes::BV);
        $dischargeMeeting->setNumber(3);
        $dischargeMeeting->setDate((new DateTime())->modify('-10 days'));
        $manager->persist($dischargeMeeting);

        // Found the organ. Per the internal regulations the board founds the committee, its (prospective) chair then
        // submits the organ regulation (commissiereglement), and only then are members installed.
        $foundationDecision = new Decision();
        $foundationDecision->setMeeting($foundingMeeting);
        $foundationDecision->setPoint(1);
        $foundationDecision->setNumber(1);

        $organ = new Foundation();
        $organ->setAbbr('ATT');
        $organ->setName('Attention Test Committee');
        $organ->setOrganType(OrganTypes::Committee);
        $organ->setSequence(1);
        $organ->setDecision($foundationDecision);
        $foundationDecision->addSubdecision($organ);

        $manager->persist($foundationDecision);
        $manager->persist($organ);

        $chair = $this->getReference(MemberFixture::REF_MEMBER_ATTN_ORDINARY_ACTIVE, MemberModel::class);

        // The chair submits the organ regulation before any member is installed.
        $this->regulateOrgan($manager, $foundingMeeting, 2, $organ, $chair);

        // B1: ordinary active member, appointed chair on founding (a chair is required; Chair counts as active).
        $this->installInOrgan($manager, $foundingMeeting, 3, $organ, $chair, InstallationFunctions::Chair);

        // B3: external active member.
        $this->installInOrgan(
            $manager,
            $foundingMeeting,
            4,
            $organ,
            $this->getReference(MemberFixture::REF_MEMBER_ATTN_EXTERNAL_ACTIVE, MemberModel::class),
        );

        // B5: graduate installed as "Inactief Lid"; surfaces only because the graduate finder treats
        // inactive organ members as active.
        $this->installInOrgan(
            $manager,
            $foundingMeeting,
            5,
            $organ,
            $this->getReference(MemberFixture::REF_MEMBER_ATTN_GRADUATE_ACTIVE, MemberModel::class),
            InstallationFunctions::InactiveMember,
        );

        // D1: installed on founding (active throughout the membership), then discharged 10 days ago. At that point, the
        // membership already expired (30 days ago). At the membership's actual end date the member was still active in
        // the organ, but the finder reads activity as of today (when they are discharged) and buckets them "ordinary
        // non-active".
        $misclassifiedInstallation = $this->installInOrgan(
            $manager,
            $foundingMeeting,
            6,
            $organ,
            $this->getReference(MemberFixture::REF_MEMBER_ATTN_MISCLASSIFIED, MemberModel::class),
        );
        $this->dischargeFromOrgan($manager, $dischargeMeeting, 1, $misclassifiedInstallation);

        $manager->flush();
    }

    /**
     * Submit the organ regulation (commissiereglement) for the given organ, authored by its chair.
     */
    private function regulateOrgan(
        ObjectManager $manager,
        Meeting $meeting,
        int $point,
        Foundation $organ,
        MemberModel $chair,
    ): void {
        $decision = new Decision();
        $decision->setMeeting($meeting);
        $decision->setPoint($point);
        $decision->setNumber(1);

        $regulation = new OrganRegulation();
        $regulation->setAbbr($organ->getAbbr());
        $regulation->setOrganType($organ->getOrganType());
        $regulation->setVersion('1.0');
        $regulation->setDate(clone $meeting->getDate());
        $regulation->setApproval(true);
        $regulation->setChanges(false);
        $regulation->setMember($chair);
        $regulation->setSequence(1);
        $regulation->setDecision($decision);
        $decision->addSubdecision($regulation);

        $manager->persist($decision);
        $manager->persist($regulation);
    }

    /**
     * Install a member into the given organ via a fresh decision on the given meeting.
     */
    private function installInOrgan(
        ObjectManager $manager,
        Meeting $meeting,
        int $point,
        Foundation $organ,
        MemberModel $member,
        InstallationFunctions $function = InstallationFunctions::Member,
    ): Installation {
        $decision = new Decision();
        $decision->setMeeting($meeting);
        $decision->setPoint($point);
        $decision->setNumber(1);

        $installation = new Installation();
        $installation->setFoundation($organ);
        $installation->setMember($member);
        $installation->setFunction($function);
        $installation->setSequence(1);
        $installation->setDecision($decision);
        $decision->addSubdecision($installation);

        $manager->persist($decision);
        $manager->persist($installation);

        return $installation;
    }

    /**
     * Discharge the given installation via a fresh decision on the given meeting.
     */
    private function dischargeFromOrgan(
        ObjectManager $manager,
        Meeting $meeting,
        int $point,
        Installation $installation,
    ): void {
        $decision = new Decision();
        $decision->setMeeting($meeting);
        $decision->setPoint($point);
        $decision->setNumber(1);

        $discharge = new Discharge();
        $discharge->setInstallation($installation);
        $discharge->setSequence(1);
        $discharge->setDecision($decision);
        $decision->addSubdecision($discharge);

        $manager->persist($decision);
        $manager->persist($discharge);
    }

    /**
     * Returns dependent fixture classes
     *
     * @return array<class-string>
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MemberFixture::class,
        ];
    }
}
