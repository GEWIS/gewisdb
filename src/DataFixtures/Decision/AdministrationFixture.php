<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\DataFixtures\Member\MemberFixture;
use App\Entity\Database\Decision;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use App\Entity\Database\Member as MemberModel;
use App\Entity\Database\SubDecision\Financial\Budget;
use App\Entity\Database\SubDecision\Financial\Statement;
use App\Entity\Database\SubDecision\Key\Granting;
use App\Entity\Database\SubDecision\Key\Withdrawal;
use App\Entity\Database\SubDecision\Minutes;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * The decisions a board takes that are not about bodies: minutes, money and key codes.
 *
 * Without these the meeting pages and the decision export only ever show installations, and the kinds of decision
 * that are hardest to get right are the ones nobody has an example of.
 */
class AdministrationFixture extends Fixture implements DependentFixtureInterface
{
    public const string REF_MEETING_BV4 = 'meeting-bv-4';
    public const string REF_KEY_GRANTING = 'key-granting';

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $meeting = new Meeting();
        $meeting->setType(MeetingTypes::BV);
        $meeting->setNumber(4);
        // Later than the board meeting DecisionFixture holds, because meetings of a type are numbered in the
        // order they are held.
        $meeting->setDate(new DateTime()->modify('-3 days'));
        $manager->persist($meeting);
        $this->addReference(
            self::REF_MEETING_BV4,
            $meeting,
        );

        $treasurer = $this->getReference(
            MemberFixture::REF_MEMBER_ATTN_ORDINARY_ACTIVE,
            MemberModel::class,
        );
        $keyholder = $this->getReference(
            MemberFixture::REF_MEMBER_STUDENT,
            MemberModel::class,
        );
        $formerKeyholder = $this->getReference(
            MemberFixture::REF_MEMBER_EXTERNAL,
            MemberModel::class,
        );

        // Approving the minutes of the first board meeting. The member is the secretary who wrote them, and the
        // content names them, so it is not optional.
        $decision = $this->decision(
            $manager,
            $meeting,
            1,
        );
        $minutes = new Minutes();
        $minutes->setMember($treasurer);
        $minutes->setTarget($this->getReference(MeetingFixture::REF_MEETING_BV1, Meeting::class));
        $minutes->setApproval(true);
        $minutes->setChanges(false);
        $minutes->setSequence(1);
        $minutes->setDecision($decision);
        $decision->addSubdecision($minutes);
        $manager->persist($minutes);

        // A budget, approved as submitted.
        $decision = $this->decision(
            $manager,
            $meeting,
            2,
        );
        $budget = new Budget();
        $budget->setName('Begroting Attention Test Committee');
        $budget->setVersion('1.0');
        $budget->setDate(new DateTime()->modify('-2 months'));
        $budget->setApproval(true);
        $budget->setChanges(false);
        $budget->setMember($treasurer);
        $budget->setSequence(1);
        $budget->setDecision($decision);
        $decision->addSubdecision($budget);
        $manager->persist($budget);

        // A statement, approved with changes — the other half of the pair, and the case where `changes` is true.
        $decision = $this->decision(
            $manager,
            $meeting,
            3,
        );
        $statement = new Statement();
        $statement->setName('Afrekening Attention Test Committee');
        $statement->setVersion('1.1');
        $statement->setDate(new DateTime()->modify('-2 months'));
        $statement->setApproval(true);
        $statement->setChanges(true);
        $statement->setMember($treasurer);
        $statement->setSequence(1);
        $statement->setDecision($decision);
        $decision->addSubdecision($statement);
        $manager->persist($statement);

        // A key code that is still held.
        $decision = $this->decision(
            $manager,
            $meeting,
            4,
        );
        $granting = new Granting();
        $granting->setMember($keyholder);
        $granting->setUntil(new DateTime()->modify('+6 months'));
        $granting->setSequence(1);
        $granting->setDecision($decision);
        $decision->addSubdecision($granting);
        $manager->persist($granting);
        $this->addReference(
            self::REF_KEY_GRANTING,
            $granting,
        );

        // And one that was granted and then withdrawn, so the withdrawal has something to point at.
        $decision = $this->decision(
            $manager,
            $meeting,
            5,
        );
        $earlier = new Granting();
        $earlier->setMember($formerKeyholder);
        $earlier->setUntil(new DateTime()->modify('+1 year'));
        $earlier->setSequence(1);
        $earlier->setDecision($decision);
        $decision->addSubdecision($earlier);
        $manager->persist($earlier);

        $withdrawal = new Withdrawal();
        $withdrawal->setGranting($earlier);
        $withdrawal->setWithdrawnOn(clone $meeting->getDate());
        $withdrawal->setSequence(2);
        $withdrawal->setDecision($decision);
        $decision->addSubdecision($withdrawal);
        $manager->persist($withdrawal);

        $manager->flush();
    }

    private function decision(
        ObjectManager $manager,
        Meeting $meeting,
        int $point,
    ): Decision {
        $decision = new Decision();
        $decision->setMeeting($meeting);
        $decision->setPoint($point);
        $decision->setNumber(1);
        $manager->persist($decision);

        return $decision;
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
            DecisionFixture::class,
        ];
    }
}
