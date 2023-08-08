<?php

declare(strict_types=1);

namespace Checker\Mapper;

use Database\Model\Member as MemberModel;
use DateTime;
use Doctrine\ORM\EntityManager;

/**
 * Member mapper
 */
class Member
{
    /**
     * Constructor
     *
     * @param EntityManager $em Doctrine entity manager.
     */
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Get a list of members whose membership should be checked against the TU/e student administration database.
     *
     * This utilises a hidden column that fixes the sorting of records that have a `lastCheckedOn` of `null`. Postgres
     * defaults to sorting `null` after everything else when using `ASC`. It does support `NULLS FIRST` after an
     * `ORDER BY` to reverse this, however, Doctrine ORM does not support this.
     *
     * The hidden column contains either 0 or 1, when `lastCheckedOn` is `null` or not (respectively). We sort on this
     * column first in ascending order, causing all records having `null` for `lastCheckedOn` to appear first.
     *
     * @return MemberModel[]
     */
    public function getMembersToCheck(int $limit): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->addSelect('CASE WHEN m.lastCheckedOn IS NULL THEN 0 ELSE 1 END AS HIDDEN fix_ordering')
            ->from('Database\Model\Member', 'm')
            ->where('m.type = \'ordinary\'')
            ->andWhere('m.tueUsername IS NOT NULL')
            ->andWhere('m.membershipEndsOn IS NULL')
            ->andWhere('m.lastCheckedOn IS NULL OR m.lastCheckedOn < CURRENT_DATE()')
            ->andWhere('m.expiration <= :endOfCurrentAssociationYear')
            ->addOrderBy('fix_ordering', 'ASC')
            ->addOrderBy('m.lastCheckedOn', 'ASC')
            ->setMaxResults($limit);

        $qb->setParameter('endOfCurrentAssociationYear', $this->getEndOfCurrentAssociationYear());

        return $qb->getQuery()->getResult();
    }

    /**
     * Get a list of members whose membership has an end date, but who are not yet "graduate".
     *
     * @return MemberModel[]
     */
    public function getEndingMembershipsWithNormalTypes(): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from('Database\Model\Member', 'm')
            ->where('m.type = \'ordinary\' OR m.type = \'external\'')
            ->andWhere('m.membershipEndsOn IS NOT NULL')
            ->andWhere('m.expiration <= :endOfCurrentAssociationYear');

        $qb->setParameter('endOfCurrentAssociationYear', $this->getEndOfCurrentAssociationYear());

        return $qb->getQuery()->getResult();
    }

    /**
     * Get a list of members whose membership is set to expire, but should automatically be renewed.
     *
     * @return MemberModel[]
     */
    public function getExpiringMembershipsWithNormalTypes(): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from('Database\Model\Member', 'm')
            ->where('m.type = \'ordinary\'')
            ->andWhere('m.membershipEndsOn IS NULL')
            ->andWhere('m.expiration <= :endOfCurrentAssociationYear');

        $qb->setParameter('endOfCurrentAssociationYear', $this->getEndOfCurrentAssociationYear());

        return $qb->getQuery()->getResult();
    }

    /**
     * Get a list of members who are hidden or whose membership has expired.
     *
     * @return MemberModel[]
     */
    public function getExpiredOrHiddenMembersWithAuthenticationKey(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m')
            ->from('Database\Model\Member', 'm')
            ->where('m.authenticationKey IS NOT NULL')
            ->andWhere('m.expiration <= CURRENT_TIMESTAMP() OR m.hidden = True');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all expiring graduates for which no renewal link exists
     * The check for hidden is required because hidden members may also expire but should not be emailed
     *
     * @param ?DateTime $expiresBefore Latest expiry date, end of current association year if null
     *
     * @return MemberModel[]
     */
    public function getExpiringGraduates(
        ?DateTime $expiresBefore = null,
        ?int $limit = null,
    ): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m')
            ->from('Database\Model\Member', 'm')
            ->where('m.type = \'graduate\'')
            ->andWhere('m.email IS NOT NULL')
            ->andWhere('m.hidden = false')
            ->andWhere('m.deleted = false')
            ->andWhere('m.expiration <= :expiresBefore');

        $qbal = $this->em->createQueryBuilder();
        $qbal->select('rl')
            ->from('Database\Model\RenewalLink', 'rl')
            ->andWhere('rl.member = m.lidnr')
            ->andWhere('rl.currentExpiration = m.expiration');

        $qb->setParameter('expiresBefore', $expiresBefore ?? $this->getEndOfCurrentAssociationYear());

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbal->getDql()),
        ));

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    private function getEndOfCurrentAssociationYear(): DateTime
    {
        $end = new DateTime();
        $end->setTime(0, 0);

        if ($end->format('m') >= 7) {
            $year = (int) $end->format('Y') + 1;
        } else {
            $year = (int) $end->format('Y');
        }

        $end->setDate($year, 7, 1);

        return $end;
    }

    /**
     * Persist a member model.
     *
     * @param MemberModel $member Member to persist.
     */
    public function persist(MemberModel $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }
}
