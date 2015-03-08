<?php
namespace Checker\Mapper;

use Database\Model\Event as EventModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;


class Installation
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
     * Returns an array of all installations that are discharged again before or during $meeting
     *
     * @param \Database\Model\Meeting $meeting Meeting for which to check
     * @return array \Database\Model\SubDecision\Installation
     */
    public function getAllInstallationsDischarged(\Database\Model\Meeting $meeting)
    {

        $qb = $this->em->createQueryBuilder();

        $qb->select('d')
            ->where('m.date <= :meeting_date')
            ->from('Database\Model\SubDecision\Discharge', 'd')
            ->innerJoin('d.decision', 'dec')
            ->innerJoin('dec.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        return $this->filterDeleted($qb->getQuery()->getResult());
    }

    /**
     * Returns an array of all installations that have bbeen done before or during $meeting
     *
     * @param \Database\Model\Meeting meeting Meeting for which to check
     * @return array \Database\Model\SubDecision\Installation
     */
    public function getAllInstallationsInstalled(\Database\Model\Meeting $meeting)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('i')
            ->where('m.date <= :meeting_date')
            ->from('Database\Model\SubDecision\Installation', 'i')
            ->innerJoin('i.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        return $this->filterDeleted($qb->getQuery()->getResult());
    }

}