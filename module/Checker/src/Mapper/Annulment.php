<?php

declare(strict_types=1);

namespace Checker\Mapper;

use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Annulment as AnnulmentModel;
use Doctrine\ORM\EntityManager;

class Annulment
{
    /**
     * Constructor
     *
     * @param EntityManager $em Doctrine entity manager.
     */
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Returns the annulments that were made during `$meeting`.
     *
     * @return AnnulmentModel[]
     */
    public function getAnnulmentsAtMeeting(MeetingModel $meeting): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from(AnnulmentModel::class, 'a')
            ->where('a.meeting_type = :meeting_type')
            ->andWhere('a.meeting_number = :meeting_number')
            ->orderBy('a.decision_point', 'ASC')
            ->addOrderBy('a.decision_number', 'ASC')
            ->addOrderBy('a.sequence', 'ASC')
            ->setParameter('meeting_type', $meeting->getType())
            ->setParameter('meeting_number', $meeting->getNumber());

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns every annulment that annuls the given decision.
     *
     * Looked up rather than read off `Decision::getAnnulledBy()`, which can only ever hand back one of them, and so
     * cannot show that a decision was annulled more than once.
     *
     * @return AnnulmentModel[]
     */
    public function getAnnulmentsForDecision(DecisionModel $decision): array
    {
        return $this->em->getRepository(AnnulmentModel::class)->findBy(['target' => $decision]);
    }
}
