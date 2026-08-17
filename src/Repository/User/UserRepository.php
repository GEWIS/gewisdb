<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByLogin(string $login): ?User
    {
        return $this->findOneBy(['login' => $login]);
    }

    /**
     * findByLogin, but always returns a user
     */
    public function findOrCreateByLogin(string $login): User
    {
        $user = $this->findByLogin($login);
        if (null !== $user) {
            return $user;
        }

        $user = new User();
        $user->setLogin($login);
        $this->persist($user);

        return $user;
    }

    public function persist(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}
