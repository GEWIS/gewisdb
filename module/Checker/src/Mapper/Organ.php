<?php

declare(strict_types=1);

namespace Checker\Mapper;

use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Abrogation as AbrogationModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Doctrine\ORM\EntityManager;

/**
 * Event mapper
 */
class Organ
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
     * Returns an array of names of all organs created before or during $meeting
     *
     * @param MeetingModel $meeting Meeting to check for
     *
     * @return FoundationModel[]
     */
    public function getAllOrganFoundations(MeetingModel $meeting): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('f')
            ->where('m.date <= :meeting_date')
            ->from(FoundationModel::class, 'f')
            ->innerJoin('f.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        /** @var FoundationModel[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filterDeleted($result);
    }

    /**
     * Returns an array of all names of organs discharged before or during $meeting
     *
     * @param MeetingModel $meeting Meeting to check for
     *
     * @return AbrogationModel[]
     */
    public function getAllOrganAbrogations(MeetingModel $meeting): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->where('m.date <= :meeting_date')
            ->from(AbrogationModel::class, 'a')
            ->innerjoin('a.foundation', 'f')
            ->innerJoin('a.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'));

        /** @var AbrogationModel[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filterDeleted($result);
    }

    /**
     * Returns all the organs created at a meeting
     *
     * @param MeetingModel $meeting The meeting the organ is created at
     *
     * @return FoundationModel[]
     */
    public function getOrgansCreatedAtMeeting(MeetingModel $meeting): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('f')
            ->where('m.number = :meeting_number')
            ->andWhere('m.type = :meeting_type')
            ->from(FoundationModel::class, 'f')
            ->innerJoin('f.decision', 'd')
            ->innerJoin('d.meeting', 'm')
            ->setParameter('meeting_number', $meeting->getNumber())
            ->setParameter('meeting_type', $meeting->getType());

        /** @var FoundationModel[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filterDeleted($result);
    }
}
