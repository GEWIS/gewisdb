<?php

namespace Database\Mapper;

use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use Doctrine\ORM\{
    EntityManager,
    EntityRepository,
};

class ProspectiveMember
{
    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * See if we can find a member with the same email.
     */
    public function hasMemberWith(string $email): bool
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(ProspectiveMemberModel::class, 'm')
            ->where("LOWER(m.email) = LOWER(:email)")
            ->setMaxResults(1);

        $qb->setParameter(':email', $email);

        $ret = $qb->getQuery()->getResult();
        return $ret !== null && count($ret) > 0;
    }

    /**
     * Search for a member.
     *
     * @return array<array-key, ProspectiveMemberModel>
     */
    public function search(string $query): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(ProspectiveMemberModel::class, 'm')
            ->where("CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere("CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :name")
            ->setMaxResults(32)
            ->orderBy('m.lidnr', 'DESC')
            ->setFirstResult(0);

        $qb->setParameter(':name', '%' . strtolower($query) . '%');

        // also allow searching for membership numbers
        if (is_numeric($query)) {
            $qb->orWhere("m.lidnr = :nr");
            $qb->setParameter(':nr', $query);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all members.
     *
     * @return array<array-key, ProspectiveMemberModel>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find a member (by lidnr).
     *
     * And calculate memberships.
     */
    public function find(int $lidnr): ?ProspectiveMemberModel
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, l')
            ->from(ProspectiveMemberModel::class, 'm')
            ->where('m.lidnr = :lidnr')
            ->leftJoin('m.lists', 'l');

        $qb->setParameter(':lidnr', $lidnr);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Persist a member model.
     */
    public function persist(ProspectiveMemberModel $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    /**
     * Remove a member.
     */
    public function remove(ProspectiveMemberModel $member)
    {
        $this->em->remove($member);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(ProspectiveMemberModel::class);
    }
}
