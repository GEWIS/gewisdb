<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\DataFixtures\Member\MemberFixture;
use App\Entity\Database\Decision;
use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\Meeting;
use App\Entity\Database\Member as MemberModel;
use App\Entity\Database\SubDecision\Discharge;
use App\Entity\Database\SubDecision\Foundation;
use App\Entity\Database\SubDecision\Installation;
use App\Entity\Database\SubDecision\OrganRegulation;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * Founds the bodies and installs the members that have to count as active organ members.
 *
 * The meetings are created here rather than in {@see MeetingFixture} because a decision's identity is derived from
 * its meeting, and Doctrine can only resolve that derived identity when the meeting is created and flushed in the
 * same fixture as the decisions referring to it.
 */
class DecisionFixture extends Fixture implements DependentFixtureInterface
{
    public const string REF_ORGAN_COMMITTEE = 'organ-att';
    public const string REF_ORGAN_FRATERNITY = 'organ-dis';

    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Two board meetings after MeetingFixture's BV 1. The founding meeting sits three years back: after everyone
        // installed had joined, and long enough ago that the installations count as current. The discharge meeting is
        // ten days back, after the misclassified member's membership had already expired.
        $founding = new Meeting();
        $founding->setType(MeetingTypes::BV);
        $founding->setNumber(2);
        $founding->setDate(new DateTime()->modify('-3 years'));
        $manager->persist($founding);

        $discharge = new Meeting();
        $discharge->setType(MeetingTypes::BV);
        $discharge->setNumber(3);
        $discharge->setDate(new DateTime()->modify('-10 days'));
        $manager->persist($discharge);

        // A fraternity may only be founded at a general members' meeting, and has been able to only there since the
        // Internal Regulations changed on 7 October 2021, so it gets a GMM of its own rather than sharing the board
        // meeting above.
        $gmm = new Meeting();
        $gmm->setType(MeetingTypes::ALV);
        $gmm->setNumber(1);
        $gmm->setDate(new DateTime()->modify('-3 years')->modify('+1 week'));
        $manager->persist($gmm);

        $this->loadCommittee(
            $manager,
            $founding,
            $discharge,
        );
        $this->loadFraternity(
            $manager,
            $gmm,
        );

        $manager->flush();
    }

    /**
     * A committee, founded the way the regulations require: the board founds it, its chair submits the committee
     * regulations, and only then are members installed.
     */
    private function loadCommittee(
        ObjectManager $manager,
        Meeting $founding,
        Meeting $discharge,
    ): void {
        $organ = $this->foundOrgan(
            $manager,
            $founding,
            1,
            'ATT',
            'Attention Test Committee',
            OrganTypes::Committee,
        );
        $this->addReference(
            self::REF_ORGAN_COMMITTEE,
            $organ,
        );

        $chair = $this->getReference(
            MemberFixture::REF_MEMBER_ATTN_ORDINARY_ACTIVE,
            MemberModel::class,
        );
        $this->regulateOrgan(
            $manager,
            $founding,
            2,
            $organ,
            $chair,
        );

        // Whoever holds a function in an organ is one of its members as well, and is installed as a member first.
        $this->installInOrgan(
            $manager,
            $founding,
            3,
            $organ,
            $chair,
            InstallationFunctions::Member,
            InstallationFunctions::Chair,
        );

        $this->installInOrgan(
            $manager,
            $founding,
            4,
            $organ,
            $this->getReference(
                MemberFixture::REF_MEMBER_ATTN_EXTERNAL_ACTIVE,
                MemberModel::class,
            ),
            InstallationFunctions::Member,
        );

        // Installed on founding, discharged ten days ago. Their membership had already expired thirty days ago, so at
        // the end of it they were still active — but activity is read as of today, which files them as non-active.
        $misclassified = $this->installInOrgan(
            $manager,
            $founding,
            5,
            $organ,
            $this->getReference(
                MemberFixture::REF_MEMBER_ATTN_MISCLASSIFIED,
                MemberModel::class,
            ),
            InstallationFunctions::Member,
        );
        $this->dischargeFromOrgan(
            $manager,
            $discharge,
            1,
            $misclassified,
        );
    }

    /**
     * A fraternity, which is the only kind of body that has inactive members: it keeps members who no longer study
     * (HR art. 13), and they then hold no function. It needs a chair and three active members, and an inactive one
     * does not count towards that, so three are installed alongside the graduate.
     */
    private function loadFraternity(
        ObjectManager $manager,
        Meeting $founding,
    ): void {
        $organ = $this->foundOrgan(
            $manager,
            $founding,
            1,
            'DIS',
            'Dispuut Testgezelschap',
            OrganTypes::Fraternity,
        );
        $this->addReference(
            self::REF_ORGAN_FRATERNITY,
            $organ,
        );

        $chair = $this->getReference(
            MemberFixture::REF_MEMBER_ATTN_ORDINARY_ACTIVE,
            MemberModel::class,
        );
        $this->regulateOrgan(
            $manager,
            $founding,
            2,
            $organ,
            $chair,
        );

        $this->installInOrgan(
            $manager,
            $founding,
            3,
            $organ,
            $chair,
            InstallationFunctions::Member,
            InstallationFunctions::Chair,
        );

        $this->installInOrgan(
            $manager,
            $founding,
            4,
            $organ,
            $this->getReference(
                MemberFixture::REF_MEMBER_ATTN_EXTERNAL_ACTIVE,
                MemberModel::class,
            ),
            InstallationFunctions::Member,
        );

        // Someone from outside the attention matrix, so that making them an active organ member does not change what
        // any of those members are there to demonstrate.
        $this->installInOrgan(
            $manager,
            $founding,
            5,
            $organ,
            $this->getReference(
                MemberFixture::REF_MEMBER_STUDENT,
                MemberModel::class,
            ),
            InstallationFunctions::Member,
        );

        // The graduate the "requiring attention" overview surfaces, because the finder counts an inactive organ
        // member as active. This is the installation that has to be in a fraternity: in a committee the checker
        // rejects it, and rightly — committees discharge whoever is no longer part of them.
        $this->installInOrgan(
            $manager,
            $founding,
            6,
            $organ,
            $this->getReference(
                MemberFixture::REF_MEMBER_ATTN_GRADUATE_ACTIVE,
                MemberModel::class,
            ),
            InstallationFunctions::InactiveMember,
        );
    }

    private function foundOrgan(
        ObjectManager $manager,
        Meeting $meeting,
        int $point,
        string $abbreviation,
        string $name,
        OrganTypes $type,
    ): Foundation {
        $decision = new Decision();
        $decision->setMeeting($meeting);
        $decision->setPoint($point);
        $decision->setNumber(1);

        $organ = new Foundation();
        $organ->setAbbr($abbreviation);
        $organ->setName($name);
        $organ->setOrganType($type);
        $organ->setSequence(1);
        $organ->setDecision($decision);
        $decision->addSubdecision($organ);

        $manager->persist($decision);
        $manager->persist($organ);

        return $organ;
    }

    /**
     * Approve the regulations of a body, authored by its chair.
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
     * Install a member in a body through a decision of its own.
     *
     * Someone holding a function is a member of the body as well, so more than one function may be given; they are
     * recorded in the order given, which is why "Lid" always comes first.
     *
     * @return Installation the installation for the first of the given functions
     */
    private function installInOrgan(
        ObjectManager $manager,
        Meeting $meeting,
        int $point,
        Foundation $organ,
        MemberModel $member,
        InstallationFunctions ...$functions,
    ): Installation {
        $decision = new Decision();
        $decision->setMeeting($meeting);
        $decision->setPoint($point);
        $decision->setNumber(1);
        $manager->persist($decision);

        $installations = [];
        $sequence = 1;

        foreach ($functions as $function) {
            $installation = new Installation();
            $installation->setFoundation($organ);
            $installation->setMember($member);
            $installation->setFunction($function);
            $installation->setSequence($sequence++);
            $installation->setDecision($decision);
            $decision->addSubdecision($installation);

            $manager->persist($installation);

            $installations[] = $installation;
        }

        return $installations[0];
    }

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
     * @return array<class-string<FixtureInterface>>
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MemberFixture::class,
            MeetingFixture::class,
        ];
    }
}
