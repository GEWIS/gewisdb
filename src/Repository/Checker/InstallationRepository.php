<?php

declare(strict_types=1);

namespace App\Repository\Checker;

use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision\Discharge;
use App\Entity\Database\SubDecision\Installation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Installation>
 */
class InstallationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly AnnulledSubDecisionFilter $filter,
    ) {
        parent::__construct(
            $registry,
            Installation::class,
        );
    }

    /**
     * Returns an array of all installations that are discharged again before or during $meeting
     *
     * @return Discharge[]
     */
    public function getAllInstallationsDischarged(Meeting $meeting): array
    {
        $qb = $this->getEntityManager()->getRepository(Discharge::class)->createQueryBuilder('d');

        $qb->where('m.date <= :meeting_date')
            ->innerJoin(
                'd.decision',
                'dec',
            )
            ->innerJoin(
                'dec.meeting',
                'm',
            )
            ->setParameter(
                'meeting_date',
                $meeting->getDate()->format('Y-m-d'),
            );

        /** @var Discharge[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filter->filterDeleted($result);
    }

    /**
     * Returns an array of all installations that have been done before or during `$meeting`.
     *
     * @return Installation[]
     */
    public function getAllInstallationsInstalled(Meeting $meeting): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb->where('m.date <= :meeting_date')
            ->innerJoin(
                'i.decision',
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

        /** @var Installation[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filter->filterDeleted($result);
    }
}
