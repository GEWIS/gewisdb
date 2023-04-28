<?php

declare(strict_types=1);

namespace Report\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Report\Model\Member as MemberModel;
use Report\Model\OrganMember as OrganMemberModel;

class Member
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Find a member (by lidnr).
     *
     * Do not calculate memberships.
     */
    public function findSimple(int $lidnr): ?MemberModel
    {
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->where('m.lidnr = :lidnr')
            ->orderBy('m.lidnr', 'DESC');

        $qb->setParameter(':lidnr', $lidnr);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all non-hidden and non-deleted members.
     *
     * @return array<array-key, MemberModel>
     */
    public function findNormal(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('m');

        $qb->where('m.expiration >= CURRENT_TIMESTAMP()')
            ->andWhere('m.hidden = false')
            ->andWhere('m.deleted = false')
            ->setMaxResults(32)
            ->setFirstResult(0);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find members that are in at least one organ currently
     *
     * @return array<array-key, MemberModel>
     */
    public function findActive(bool $includeOrganMembership = false): array
    {
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->leftJoin(OrganMemberModel::class, 'om', Join::WITH, 'm.lidnr = om.member')
            ->where('om.dischargeDate IS NULL OR om.dischargeDate > CURRENT_DATE()')
            ->andWhere('om.installDate < CURRENT_DATE()')
            ->andWhere('om.function <> \'\'')
            ->andWhere('om.function <> \'Inactief Lid\'');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(MemberModel::class);
    }
}
