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
    use Filter;

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
     * Returns an array of names of all organs created before or during $meeting
     *
     * @param \Database\Model\Meeting $meeting Meeting to check for
     * @return array string
     */
    public function getAllOrgansCreated(\Database\Model\Meeting $meeting)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('f')
            ->where('m.date <= :meeting_date')
            ->from('Database\Model\SubDecision\Foundation', 'f')
            ->innerJoin('f.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        $organs = $this->filterDeleted($qb->getQuery()->getResult());
        $organNames = array_map(
            function ($organ) {
                return $organ->getName();
            },
            $organs
        );

        return $organNames;
    }

    /**
     * Returns an array of all names of organs discharged before or during $meeting
     *
     * @param \Database\Model\Meeting $meeting Meeting to check for
     * @return array string
     */
    public function getAllOrgansDeleted(\Database\Model\Meeting $meeting)
    {
        $qb = $this->em->createQueryBuilder();


        $qb->select('a')
            ->where('m.date <= :meeting_date')
            ->from('Database\Model\SubDecision\Abrogation', 'a')
            ->innerjoin('a.foundation', 'f')
            ->innerJoin('a.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        $abrogations =  $this->filterDeleted($qb->getQuery()->getResult());
        $organNames = array_map(
            function ($abrogation) {
                return $abrogation->getFoundation()->getName();
            },
            $abrogations
        );

        return $organNames;
    }

    /**
     * Returns all the organs created at a meeting
     *
     * @param \Database\Model\Meeting The meeting the organ is created at
     * @return array \Database\Model\SubDecision\Foundation
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

        return $this->filterDeleted($qb->getQuery()->getResult());
    }
}
