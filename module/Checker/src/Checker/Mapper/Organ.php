<?php

namespace Checker\Mapper;

use Database\Model\Event as EventModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

/**
 * Event mapper
 */
class Organ
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
     * Returns an array of all organs created.
     * @return array
     */
    public function getAllOrgansCreated(\Database\Model\Meeting $meeting)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('f.name')
            ->where('m.date <= :meeting_date')
            ->from('Database\Model\SubDecision\Foundation', 'f')
            ->innerJoin('f.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));
        // TODO: minus deleted organ creations
        return $qb->getQuery()->getResult();
    }

    /**
     * Returns an array of all organs
     * @return array
     */
    public function getAllOrgansDeleted(\Database\Model\Meeting $meeting)
    {
        $qb = $this->em->createQueryBuilder();


        $qb->select('f.name')
            ->where('m.date <= :meeting_date')
            ->from('Database\Model\SubDecision\Abrogation', 'a')
            ->innerjoin('a.foundation', 'f')
            ->innerJoin('a.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        // TODO: Minus deleted organ deletions
        return $qb->getQuery()->getResult();
    }

    /**
     * Returns all the organs created at a meeting
     *
     * @param \Database\Model\Meeting The meeting the organ is created at
     * @return array
     */
    public function getOrgansCreatedAtMeeting(\Database\Model\Meeting $meeting)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('f')
            ->where('m.number = :meeting_number')
            ->andWhere('m.type = :meeting_type')
            ->from('Database\Model\SubDecision\Foundation', 'f')
            ->innerJoin('f.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_number', $meeting->getNumber())
            ->setParameter('meeting_type', $meeting->getType());
        // TODO: minus deleted organ creations
        return $qb->getQuery()->getResult();
    }
}
