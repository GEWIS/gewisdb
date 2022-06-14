<?php

namespace Checker\Mapper;

use Database\Model\Member as MemberModel;
use Doctrine\ORM\EntityManager;

/**
 * Member mapper
 */
class Member
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
     * @param int $limit
     *
     * @return array
     */
    public function getMembersToCheck($limit)
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
     * @return array
     */
    public function getEndingMembershipsWithNormalTypes()
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
     * @return \DateTime
     */
    private function getEndOfCurrentAssociationYear()
    {
        $end = new \DateTime();
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
    public function persist(MemberModel $member)
    {
        $this->em->persist($member);
        $this->em->flush();
    }
}
