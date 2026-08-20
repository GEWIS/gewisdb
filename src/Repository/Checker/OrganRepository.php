<?php

declare(strict_types=1);

namespace App\Repository\Checker;

use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision\Abrogation;
use App\Entity\Database\SubDecision\Foundation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Foundation>
 */
class OrganRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly AnnulledSubDecisionFilter $filter,
    ) {
        parent::__construct(
            $registry,
            Foundation::class,
        );
    }

    /**
     * Returns an array of names of all organs created before or during $meeting
     *
     * @param Meeting $meeting Meeting to check for
     *
     * @return Foundation[]
     */
    public function getAllOrganFoundations(Meeting $meeting): array
    {
        $qb = $this->createQueryBuilder('f');

        $qb->where('m.date <= :meeting_date')
            ->innerJoin(
                'f.decision',
                'd',
            )
            ->innerJoin(
                'd.meeting',
                'm',
            )
            ->setParameter(
                'meeting_date',
                $meeting->getDate()->format('Y-m-d'),
            );

        /** @var Foundation[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filter->filterDeleted($result);
    }

    /**
     * Returns an array of all names of organs discharged before or during $meeting
     *
     * @param Meeting $meeting Meeting to check for
     *
     * @return Abrogation[]
     */
    public function getAllOrganAbrogations(Meeting $meeting): array
    {
        $qb = $this->getEntityManager()->getRepository(Abrogation::class)->createQueryBuilder('a');

        $qb->where('m.date <= :meeting_date')
            ->innerJoin(
                'a.foundation',
                'f',
            )
            ->innerJoin(
                'a.decision',
                'd',
            )
            ->innerJoin(
                'd.meeting',
                'm',
            )
            ->setParameter(
                'meeting_date',
                $meeting->getDate()->format('Y-m-d'),
            );

        /** @var Abrogation[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filter->filterDeleted($result);
    }

    /**
     * Returns all the organs created at a meeting
     *
     * @param Meeting $meeting The meeting the organ is created at
     *
     * @return Foundation[]
     */
    public function getOrgansCreatedAtMeeting(Meeting $meeting): array
    {
        $qb = $this->createQueryBuilder('f');

        $qb->where('m.number = :meeting_number')
            ->andWhere('m.type = :meeting_type')
            ->innerJoin(
                'f.decision',
                'd',
            )
            ->innerJoin(
                'd.meeting',
                'm',
            )
            ->setParameter(
                'meeting_number',
                $meeting->getNumber(),
            )
            ->setParameter(
                'meeting_type',
                $meeting->getType(),
            );

        /** @var Foundation[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filter->filterDeleted($result);
    }
}
