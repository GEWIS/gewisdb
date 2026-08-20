<?php

declare(strict_types=1);

namespace App\DataFixtures\Mailing;

use App\DataFixtures\Member\MemberFixture;
use App\Entity\Database\MailingList;
use App\Entity\Database\MailingListMember;
use App\Entity\Database\Member as MemberModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Override;

/**
 * Who is on which list.
 *
 * Without these the synchronisation has nothing to carry to either server, so nothing about the mailing list
 * integration can be seen to work. The three states a membership can be in are all here: one waiting to be created,
 * one that has been carried across already, and one waiting to be removed again.
 */
class MailingListMembershipFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $announcements = $this->getReference(
            MailingListFixture::REF_LIST_ANNOUNCEMENTS,
            MailingList::class,
        );
        $activities = $this->getReference(
            MailingListFixture::REF_LIST_ACTIVITIES,
            MailingList::class,
        );

        $student = $this->getReference(
            MemberFixture::REF_MEMBER_STUDENT,
            MemberModel::class,
        );
        $external = $this->getReference(
            MemberFixture::REF_MEMBER_EXTERNAL,
            MemberModel::class,
        );
        $graduate = $this->getReference(
            MemberFixture::REF_MEMBER_GRADUATE,
            MemberModel::class,
        );

        // Everyone is on the announcements list, which is what makes it the one the registration form does not
        // offer. The first of these is still to be carried across, so a sync has something to do.
        $manager->persist($this->subscribe($announcements, $student));

        $carried = $this->subscribe(
            $announcements,
            $external,
        );
        $carried->setToBeCreated(false);
        $carried->setLastSyncOn();
        $carried->setLastSyncSuccess(true);
        $manager->persist($carried);

        // A membership on its way out: the record stays until the servers have been told.
        $leaving = $this->subscribe(
            $activities,
            $graduate,
        );
        $leaving->setToBeCreated(false);
        $leaving->setToBeDeleted(true);
        $leaving->setLastSyncOn();
        $leaving->setLastSyncSuccess(true);
        $manager->persist($leaving);

        $manager->persist($this->subscribe($activities, $student));

        $manager->flush();
    }

    private function subscribe(
        MailingList $list,
        MemberModel $member,
    ): MailingListMember {
        $membership = new MailingListMember();
        $membership->setMailingList($list);
        $membership->setMember($member);
        $membership->setEmail(
            $member->getEmail() ?? throw new LogicException('The seeded member has no e-mail address.'),
        );

        return $membership;
    }

    /**
     * @return array<class-string<FixtureInterface>>
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MailingListFixture::class,
            MemberFixture::class,
        ];
    }
}
