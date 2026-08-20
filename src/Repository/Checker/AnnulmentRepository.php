<?php

declare(strict_types=1);

namespace App\Repository\Checker;

use App\Entity\Database\Decision;
use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision\Annulment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Annulment>
 */
class AnnulmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Annulment::class,
        );
    }

    /**
     * Returns the annulments that were made during `$meeting`.
     *
     * @return Annulment[]
     */
    public function getAnnulmentsAtMeeting(Meeting $meeting): array
    {
        $qb = $this->createQueryBuilder('a');

        $qb->where('a.meeting_type = :meeting_type')
            ->andWhere('a.meeting_number = :meeting_number')
            ->orderBy(
                'a.decision_point',
                'ASC',
            )
            ->addOrderBy(
                'a.decision_number',
                'ASC',
            )
            ->addOrderBy(
                'a.sequence',
                'ASC',
            )
            ->setParameter(
                'meeting_type',
                $meeting->getType(),
            )
            ->setParameter(
                'meeting_number',
                $meeting->getNumber(),
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns every annulment that annuls the given decision.
     *
     * Looked up rather than read off `Decision::getAnnulledBy()`, which can only ever hand back one of them, and so
     * cannot show that a decision was annulled more than once.
     *
     * @return Annulment[]
     */
    public function getAnnulmentsForDecision(Decision $decision): array
    {
        return $this->findBy(['target' => $decision]);
    }
}
