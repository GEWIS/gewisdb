<?php

declare(strict_types=1);

namespace User\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use User\Model\User as UserModel;

class UserMapper
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * @return array<array-key, UserModel>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    public function find(int $id): ?UserModel
    {
        return $this->getRepository()->find($id);
    }

    public function findByLogin(string $login): ?UserModel
    {
        return $this->getRepository()->findOneBy(['login' => $login]);
    }

    public function persist(UserModel $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function remove(UserModel $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    /**
     * findByLogin, but always returns a user
     */
    public function findOrCreateByLogin(string $login): UserModel
    {
        $user = $this->findByLogin($login);
        if (null !== $user) {
            return $user;
        }

        $user = new UserModel();
        $user->setLogin($login);
        $this->persist($user);

        return $user;
    }

    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(UserModel::class);
    }
}
