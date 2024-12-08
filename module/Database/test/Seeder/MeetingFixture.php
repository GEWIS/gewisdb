<?php

declare(strict_types=1);

namespace DatabaseTest\Seeder;

use Application\Model\Enums\MeetingTypes;
use Database\Model\Meeting;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class MeetingFixture extends AbstractFixture
{
    public const REF_MEETING_BV1 = 'bv-1';

    public function load(ObjectManager $manager): void
    {
        $meeting = new Meeting();
        $meeting->setDate(new DateTime('2000-01-01'));
        $meeting->setNumber(1);
        $meeting->setType(MeetingTypes::BV);

        $manager->persist($meeting);
        $this->addReference(self::REF_MEETING_BV1, $meeting);

        $manager->flush();
    }
}
