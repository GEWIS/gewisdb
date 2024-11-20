<?php

declare(strict_types=1);

namespace UserTest\Seeder;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use User\Model\User;

class UserFixture extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setLogin('admin');
        $user->setPassword('$2y$13$smUYvCkgowlfHOFrogwcPONGDFmcylKHmTOZQAks9cDvs15tPxR2a'); // == gewisdbgewis

        $manager->persist($user);
        $this->addReference('user-admin', $user);

        $manager->flush();
    }
}
