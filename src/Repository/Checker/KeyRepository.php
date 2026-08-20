<?php

declare(strict_types=1);

namespace App\Repository\Checker;

use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision\Key\Granting;
use App\Entity\Database\SubDecision\Key\Withdrawal;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Queries over the key code grantings and withdrawals of a meeting.
 *
 * Grantings and withdrawals are separate entities of which neither owns the other, so this is a query service
 * instead of a repository bound to one of them.
 */
class KeyRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AnnulledSubDecisionFilter $filter,
    ) {
    }

    /**
     * Returns all the key code grantings in a meeting
     *
     * @return Granting[]
     */
    public function findKeysGrantedDuringMeeting(Meeting $meeting): array
    {
        $qb = $this->em->getRepository(Granting::class)->createQueryBuilder('k');

        $qb->innerJoin(
            'k.decision',
            'd',
        )
            ->innerJoin(
                'd.meeting',
                'm',
            )
            ->where('m.number = :meeting_number')
            ->andWhere('m.type = :meeting_type')
            ->setParameter(
                'meeting_number',
                $meeting->getNumber(),
            )
            ->setParameter(
                'meeting_type',
                $meeting->getType(),
            );

        /** @var Granting[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filter->filterDeleted($result);
    }

    /**
     * Returns all the key code withdrawals in a meeting
     *
     * @return Withdrawal[]
     */
    public function findKeysWithdrawnDuringMeeting(Meeting $meeting): array
    {
        $qb = $this->em->getRepository(Withdrawal::class)->createQueryBuilder('k');

        $qb->innerJoin(
            'k.decision',
            'd',
        )
            ->innerJoin(
                'd.meeting',
                'm',
            )
            ->where('m.number = :meeting_number')
            ->andWhere('m.type = :meeting_type')
            ->setParameter(
                'meeting_number',
                $meeting->getNumber(),
            )
            ->setParameter(
                'meeting_type',
                $meeting->getType(),
            );

        /** @var Withdrawal[] $result */
        $result = $qb->getQuery()->getResult();

        return $this->filter->filterDeleted($result);
    }
}
