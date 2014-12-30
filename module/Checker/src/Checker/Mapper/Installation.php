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


    public function getAllInstallationsDischarged($meetingNr)
    {

        $qb = $this->em->createQueryBuilder();

        $qb->select('d')
            ->where('d.meeting_number <= :meeting_number')
            ->from('Database\Model\SubDecision\Discharge', 'd')
            ->setParameter('meeting_number', $meetingNr);

        // TODO: minus deleted decision
        return $qb->getQuery()->getResult();
    }

    public function getAllInstallationsInstalled($meetingNr)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('i')
            ->where('i.meeting_number <= :meeting_number')
            ->from('Database\Model\SubDecision\Installation', 'i')
            ->setParameter('meeting_number', $meetingNr);

        // TODO: minus deleted decision
        return $qb->getQuery()->getResult();
    }


}