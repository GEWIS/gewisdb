<?php

declare(strict_types=1);

namespace Checker\Mapper;

use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Discharge as DischargeModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use Doctrine\ORM\EntityManager;

class Installation
{
    use Filter;

    /**
     * Constructor
     *
     * @param EntityManager $em Doctrine entity manager.
     */
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Returns an array of all installations that are discharged again before or during $meeting
     *
     * @return array<array-key, DischargeModel>
     */
    public function getAllInstallationsDischarged(MeetingModel $meeting): array
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
     * Returns an array of all installations that have been done before or during `$meeting`.
     *
     * @return array<array-key, InstallationModel>
     */
    public function getAllInstallationsInstalled(MeetingModel $meeting): array
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
