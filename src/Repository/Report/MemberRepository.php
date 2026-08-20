<?php

declare(strict_types=1);

namespace App\Repository\Report;

use App\Entity\Report\Member;
use App\Entity\Report\OrganMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Member>
 */
class MemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Member::class,
        );
    }

    /**
     * Find a member (by lidnr).
     *
     * Do not calculate memberships.
     */
    public function findSimple(int $lidnr): ?Member
    {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.lidnr = :lidnr')
            ->orderBy(
                'm.lidnr',
                'DESC',
            );

        $qb->setParameter(
            ':lidnr',
            $lidnr,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all non-hidden and non-deleted members.
     *
     * @return array<array-key, Member>
     */
    public function findNormal(): array
    {
        $qb = $this->createQueryBuilder('m');

        // Ordering is not cosmetic here: combined with the row limit below, an unordered query lets PostgreSQL return
        // any 32 of the eligible members, and a different 32 on the next call. Consumers of `GET /api/members` cannot
        // rely on the order because there was none, so pinning it to lidnr is safe and makes the endpoint repeatable.
        // The limit itself is still wrong — see GH-575 for replacing it with real pagination.
        $qb->where('m.expiration >= CURRENT_TIMESTAMP()')
            ->andWhere('m.hidden = false')
            ->andWhere('m.deleted = false')
            ->orderBy(
                'm.lidnr',
                'ASC',
            )
            ->setMaxResults(32)
            ->setFirstResult(0);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find members that are in at least one organ currently
     *
     * @return array<array-key, Member>
     */
    public function findActive(bool $includeInactiveFraternity = false): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->leftJoin(
            OrganMember::class,
            'om',
            Join::WITH,
            'm.lidnr = om.member',
        )
            ->where('om.dischargeDate IS NULL OR om.dischargeDate > CURRENT_DATE()')
            ->andWhere('om.installDate <= CURRENT_DATE()')
            ->andWhere('om.function <> \'\'');

        if (!$includeInactiveFraternity) {
            $qb->andWhere('om.function <> \'Inactief Lid\'');
        }

        // Unlike findNormal() this is not row-limited, so an unordered query returns the whole set either way — but it
        // returns it in whatever order the rows happen to sit in, which changes after any ReportDB regeneration.
        $qb->orderBy(
            'm.lidnr',
            'ASC',
        );

        return $qb->getQuery()->getResult();
    }
}
