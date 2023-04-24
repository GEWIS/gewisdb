<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\MemberUpdate as MemberUpdateModel;
use Doctrine\ORM\{
    EntityManager,
    EntityRepository,
};

class MemberUpdate
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    public function getPendingUpdates(): array
    {
        return $this->getRepository()->findAll();
    }

    public function find(int $lidnr): ?MemberUpdateModel
    {
        return $this->getRepository()->find($lidnr);
    }

    /**
     * Persist a member update model.
     */
    public function persist(MemberUpdateModel $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    /**
     * Remove a member.
     */
    public function remove(MemberUpdateModel $member): void
    {
        $this->em->remove($member);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(MemberUpdateModel::class);
    }
}
