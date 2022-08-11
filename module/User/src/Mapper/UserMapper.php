<?php

namespace User\Mapper;

use Doctrine\ORM\{
    EntityRepository,
    EntityManager,
};
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

    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(UserModel::class);
    }
}
