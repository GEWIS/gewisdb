<?php

declare(strict_types=1);

namespace UserTest\Seeder;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use User\Model\User;

class UserFixture extends AbstractFixture
{
    public const REF_ADMIN_USER = 'admin-user';

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setLogin('admin');
        $user->setPassword('$2y$13$smUYvCkgowlfHOFrogwcPONGDFmcylKHmTOZQAks9cDvs15tPxR2a'); // == gewisdbgewis

        $manager->persist($user);
        $this->addReference(self::REF_ADMIN_USER, $user);

        $manager->flush();
    }
}
