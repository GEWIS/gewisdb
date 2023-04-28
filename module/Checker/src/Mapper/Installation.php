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
     * @return DischargeModel[]
     */
    public function getAllInstallationsDischarged(MeetingModel $meeting): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('d')
            ->where('m.date <= :meeting_date')
            ->from(DischargeModel::class, 'd')
            ->innerJoin('d.decision', 'dec')
            ->innerJoin('dec.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        /** @var DischargeModel[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filterDeleted($result);
    }

    /**
     * Returns an array of all installations that have been done before or during `$meeting`.
     *
     * @return InstallationModel[]
     */
    public function getAllInstallationsInstalled(MeetingModel $meeting): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('i')
            ->where('m.date <= :meeting_date')
            ->from(InstallationModel::class, 'i')
            ->innerJoin('i.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        /** @var InstallationModel[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filterDeleted($result);
    }
}
