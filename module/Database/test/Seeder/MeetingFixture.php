<?php

declare(strict_types=1);

namespace DatabaseTest\Seeder;

use Application\Model\Enums\MeetingTypes;
use Database\Model\Decision;
use Database\Model\Enums\BoardFunctions;
use Database\Model\Meeting;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Board\Installation as BoardInstallationSubDecision;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MeetingFixture extends AbstractFixture implements DependentFixtureInterface
{
    public const REF_MEETING_BV1 = 'bv-1';
    public const REF_SUBDEC_BOARDINSTALL = 'subdecision-boardinstall';

    public function load(ObjectManager $manager): void
    {
        $meeting = new Meeting();
        $meeting->setDate(new DateTime('2000-01-01'));
        $meeting->setNumber(1);
        $meeting->setType(MeetingTypes::BV);

        $manager->persist($meeting);
        $this->addReference(self::REF_MEETING_BV1, $meeting);

        /**
         * Board installation
         */
        $decision = new Decision();
        $decision->setMeeting($meeting);
        $decision->setPoint(1);
        $decision->setNumber(1);
        $subDecision = new BoardInstallationSubDecision();
        $subDecision->setDate(new DateTime('2000-01-01'));
        $subDecision->setFunction(BoardFunctions::Chair);
        $subDecision->setMember($this->getReference(MemberFixture::REF_MEMBER_STUDENT, MemberModel::class));
        $subDecision->setSequence(1);
        $subDecision->setDecision($decision);
        $this->setReference(self::REF_SUBDEC_BOARDINSTALL, $subDecision);
        $decision->addSubdecision($subDecision);
        $manager->persist($decision);
        $manager->persist($subDecision);
        $manager->flush();
    }

    /**
     * Returns dependent fixture classes
     *
     * @return array<class>
     */
    public function getDependencies(): array
    {
        return [
            MemberFixture::class,
        ];
    }
}
