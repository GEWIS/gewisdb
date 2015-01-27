<?php
namespace Checker\Mapper;

use Database\Model\Event as EventModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;


class Installation {
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


    public function getAllInstallationsDischarged(\Database\Model\Meeting $meeting)
    {

        $qb = $this->em->createQueryBuilder();

        $qb->select('d')
            ->where('m.date <= :meeting_date')
            ->from('Database\Model\SubDecision\Discharge', 'd')
            ->innerJoin('d.decision', 'dec')
            ->innerJoin('dec.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        // TODO: minus deleted decision
        return $qb->getQuery()->getResult();
    }

    public function getAllInstallationsInstalled(\Database\Model\Meeting $meeting)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('i')
            ->where('m.date <= :meeting_date')
            ->from('Database\Model\SubDecision\Installation', 'i')
            ->innerJoin('i.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        // TODO: minus deleted decision
        return $qb->getQuery()->getResult();
    }


}