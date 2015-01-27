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
            ->where('f.meeting_number <= :meeting_number')
            ->from('Database\Model\SubDecision\Foundation', 'f')
            ->setParameter('meeting_number', $meeting->getNumber());

        // TODO: minus deleted organs
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
            ->where('a.meeting_number <= :meeting_number')
            ->from('Database\Model\SubDecision\Abrogation', 'a')
            ->innerJoin('Database\Model\SubDecision\Foundation', 'f')
            ->setParameter('meeting_number', $meeting->getNumber());

        // TODO: Minus deleted organs
        return $qb->getQuery()->getResult();
    }
}
