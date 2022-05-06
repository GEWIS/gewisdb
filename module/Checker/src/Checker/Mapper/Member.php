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
     * @param int $limit
     *
     * @return array
     */
    public function getMembersToCheck($limit)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from('Database\Model\Member', 'm')
            ->where('m.type = \'ordinary\'')
            ->andWhere('m.tuenumber IS NOT NULL')
            ->andWhere('m.membershipEndsOn IS NULL')
            ->andWhere('m.lastCheckedOn IS NULL OR m.lastCheckedOn < CURRENT_DATE()')
            ->orderBy('m.lastCheckedOn', 'ASC')
            ->setMaxResults($limit);

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
            ->andWhere('m.membershipEndsOn IS NOT NULL');

        return $qb->getQuery()->getResult();
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
