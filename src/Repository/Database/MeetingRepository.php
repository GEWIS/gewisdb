<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision;
use App\Entity\Database\SubDecision\Annulment;
use App\Entity\Database\SubDecision\Board\Discharge as BoardDischarge;
use App\Entity\Database\SubDecision\Board\Installation as BoardInstallation;
use App\Entity\Database\SubDecision\Board\Release as BoardRelease;
use App\Entity\Database\SubDecision\Key\Granting as KeyGranting;
use App\Entity\Database\SubDecision\Key\Withdrawal as KeyWithdrawal;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Meeting>
 */
use function array_values;
use function implode;
use function str_replace;
use function strtolower;

/**
 * @extends ServiceEntityRepository<Meeting>
 */
class MeetingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Meeting::class,
        );
    }

    /**
     * Check if a model is managed.
     */
    public function isManaged(Meeting $meeting): bool
    {
        return $this->getEntityManager()->getUnitOfWork()->isInIdentityMap($meeting);
    }

    /**
     * Search for a meeting.
     *
     * @return Meeting[]
     */
    public function searchMeeting(string $query): array
    {
        $qb = $this->createQueryBuilder('m');

        $fields = [];
        $fields[] = 'LOWER(m.type)';
        $fields[] = 'm.number';
        $fields = implode(
            ', ',
            $fields,
        );
        $fields = 'CONCAT(' . $fields . ')';

        $qb->where($fields . ' LIKE :search')
            ->orWhere('CONCAT(m.number, \'\') LIKE :search')
            ->orderBy(
                'm.date',
                'DESC',
            );

        $qb->setParameter(
            ':search',
            str_replace(
                ' ',
                '',
                strtolower($query),
            ) . '%',
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * One page of meetings, most recent first.
     *
     * Virtual meetings sort after real ones of the same date: they are a bookkeeping device for decisions that were
     * never taken in a room, so they should not lead the list.
     *
     * @return Paginator<Meeting>
     */
    public function paginateForOverview(
        int $page,
        int $pageSize,
        ?MeetingTypes $type = null,
    ): Paginator {
        $qb = $this->createQueryBuilder('m')
            ->addSelect('(CASE WHEN m.type = :virtual_meeting THEN 1 ELSE 0 END) AS HIDDEN virtSort')
            ->setParameter(
                'virtual_meeting',
                MeetingTypes::VIRT,
            )
            ->addOrderBy(
                'm.date',
                'DESC',
            )
            ->addOrderBy(
                'virtSort',
                'ASC',
            )
            // Type and number are the identity of a meeting, and they are ordered on so that two meetings sharing a
            // date and a virtSort cannot swap places between the count query and the page query, which would show one
            // of them twice and leave the other out of the register entirely.
            ->addOrderBy(
                'm.type',
                'ASC',
            )
            ->addOrderBy(
                'm.number',
                'ASC',
            )
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        if (null !== $type) {
            $qb->andWhere('m.type = :type')
                ->setParameter(
                    'type',
                    $type,
                );
        }

        return new Paginator(
            $qb->getQuery(),
            false,
        );
    }

    /**
     * How many meetings each type has, so the chips can say so before they are clicked.
     *
     * @return array<string, int> keyed by the value of `MeetingTypes`, in the order that enum declares them
     */
    public function countsByType(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select(
                'm.type AS type',
                'COUNT(m.number) AS total',
            )
            ->groupBy('m.type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['type']->value] = (int) $row['total'];
        }

        // A type nobody has minuted does not come back from a `GROUP BY`, and it is still a type: it is a zero, not
        // a missing row.
        $byType = [];
        foreach (MeetingTypes::cases() as $case) {
            $byType[$case->value] = $counts[$case->value] ?? 0;
        }

        return $byType;
    }

    /**
     * How many decisions each of the given meetings holds, keyed by type and number.
     *
     * Counted for the visible page in one query rather than read off each meeting, so the table costs two queries
     * whatever the page size.
     *
     * @param Meeting[] $meetings
     *
     * @return array<string, int>
     */
    public function decisionCountsFor(array $meetings): array
    {
        if ([] === $meetings) {
            return [];
        }

        $qb = $this->createQueryBuilder('m')
            ->select(
                'm.type AS type',
                'm.number AS number',
                'COUNT(d) AS total',
            )
            ->leftJoin(
                'm.decisions',
                'd',
            )
            ->groupBy('m.type')
            ->addGroupBy('m.number');

        // A meeting is identified by its type and its number together, and a composite key cannot be bound as one
        // parameter, so the page is named as an explicit list of pairs. Assembled before it is applied, so the
        // condition is never added empty.
        $pairs = [];

        foreach (array_values($meetings) as $index => $meeting) {
            $pairs[] = $qb->expr()->andX(
                'm.type = :type' . $index,
                'm.number = :number' . $index,
            );
            $qb->setParameter(
                'type' . $index,
                $meeting->getType(),
            )
                ->setParameter(
                    'number' . $index,
                    $meeting->getNumber(),
                );
        }

        $rows = $qb->andWhere($qb->expr()->orX(...$pairs))->getQuery()->getResult();

        $counts = [];

        foreach ($rows as $row) {
            $counts[$row['type']->value . '-' . $row['number']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Find all meetings, each paired with its decision count.
     *
     * Cannot be called findAll(): the parent declares list<Meeting> and this returns pairs, which is an
     * incompatible override rather than a widened one.
     *
     * @return array<array-key, array{0: Meeting, 1: int}>
     */
    public function findAllWithDecisionCount(bool $asc = false): array
    {
        $qb = $this->createQueryBuilder('m');

        $qb->addSelect('COUNT(d)')
                ->addSelect('(CASE WHEN m.type = :virtual_meeting THEN 1 ELSE 0 END) AS HIDDEN virtSort')
                ->leftJoin(
                    'm.decisions',
                    'd',
                )
                ->groupBy('m')
            ->setParameter(
                ':virtual_meeting',
                MeetingTypes::VIRT,
            );

        if ($asc) {
            $qb->addOrderBy(
                'm.date',
                'ASC',
            );
        } else {
            $qb->addOrderBy(
                'm.date',
                'DESC',
            );
        }

        // A meeting held to put right what an earlier one got wrong is a virtual one on that same date, so it goes
        // last of that date. Beyond that the date says nothing about which of two meetings came first, and a replay
        // has to make the same choice every time, so type and number settle the rest.
        $qb->addOrderBy(
            'virtSort',
            'ASC',
        )
            ->addOrderBy(
                'm.type',
                'ASC',
            )
            ->addOrderBy(
                'm.number',
                'ASC',
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the last meeting.
     */
    public function findLast(): ?Meeting
    {
        $qb = $this->createQueryBuilder('m');
        $qb->leftJoin(
            'm.decisions',
            'd',
        )
            ->orderBy(
                'm.date',
                'DESC',
            )
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find decisions by given meetings.
     *
     * @param array<array-key, array{type: string, number: int}> $meetings
     *
     * @return Decision[]
     */
    public function findDecisionsByMeetings(array $meetings): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('d, s')
            ->from(
                Decision::class,
                'd',
            )
            ->join(
                'd.meeting',
                'm',
            )
            ->leftJoin(
                'd.subdecisions',
                's',
            )
            ->orderBy(
                'm.type',
                'ASC',
            )
            ->addOrderBy(
                'm.number',
                'ASC',
            )
            ->addOrderBy(
                'd.point',
                'ASC',
            )
            ->addOrderBy(
                'd.number',
                'ASC',
            )
            ->addOrderBy(
                's.sequence',
                'ASC',
            );

        $num = 0;
        foreach ($meetings as $meeting) {
            $qb->orWhere($qb->expr()->andX(
                $qb->expr()->eq(
                    'm.type',
                    ':type' . $num,
                ),
                $qb->expr()->eq(
                    'm.number',
                    ':number' . $num,
                ),
            ));
            $qb->setParameter(
                ':type' . $num,
                $meeting['type'],
            );
            $qb->setParameter(
                ':number' . $num,
                $meeting['number'],
            );
            $num++;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a meeting with all decisions.
     *
     * Not named find(), because EntityRepository::find() takes an identifier and cannot be narrowed.
     */
    public function findMeeting(
        MeetingTypes $type,
        int $number,
    ): ?Meeting {
        $qb = $this->createQueryBuilder('m');

        $qb->addSelect(
            'd',
            's',
            'db',
        )
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->leftJoin(
                'm.decisions',
                'd',
            )
            ->leftJoin(
                'd.subdecisions',
                's',
            )
            ->leftJoin(
                'd.annulledBy',
                'db',
            )
            ->orderBy('d.point')
            ->addOrderBy('d.number')
            ->addOrderBy('s.sequence');

        $qb->setParameter(
            ':type',
            $type,
        );
        $qb->setParameter(
            ':number',
            $number,
        );

        $res = $qb->getQuery()->getResult();

        return empty($res)
            ? null
            : $res[0];
    }

    /**
     * Find a decision.
     */
    public function findDecision(
        MeetingTypes $meetingType,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
    ): ?Decision {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('d, s')
            ->from(
                Decision::class,
                'd',
            )
            ->where('d.meeting_type = :meeting_type')
            ->andWhere('d.meeting_number = :meeting_number')
            ->andWhere('d.point = :decision_point')
            ->andWhere('d.number = :decision_number')
            ->leftJoin(
                'd.subdecisions',
                's',
            )
            ->orderBy('s.sequence');

        $qb->setParameter(
            ':meeting_type',
            $meetingType,
        );
        $qb->setParameter(
            ':meeting_number',
            $meetingNumber,
        );
        $qb->setParameter(
            ':decision_point',
            $decisionPoint,
        );
        $qb->setParameter(
            ':decision_number',
            $decisionNumber,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Search for a decision.
     *
     * Decisions that annul another decision are never returned: annulling an annulment has no well-defined meaning,
     * because an annulment has no effects of its own to revert.
     *
     * @param Meeting|null $before when given, only decisions taken before this meeting are returned, and within that
     *                             meeting only those before $beforePoint and $beforeNumber.
     *
     * @return Decision[]
     */
    public function searchDecision(
        string $query,
        bool $includeAnnulled = false,
        ?Meeting $before = null,
        ?int $beforePoint = null,
        ?int $beforeNumber = null,
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $fields = [];
        $fields[] = 'LOWER(d.meeting_type)';
        $fields[] = "' '";
        $fields[] = 'd.meeting_number';
        $fields[] = "'.'";
        $fields[] = 'd.point';
        $fields[] = "'.'";
        $fields[] = 'd.number';
        $fields[] = "' '";
        $fields = implode(
            ', ',
            $fields,
        );
        $fields = 'CONCAT(' . $fields . ')';

        $qb->select('d, s, m')
            ->from(
                Decision::class,
                'd',
            )
            ->where($fields . ' LIKE :search')
            ->leftJoin(
                'd.subdecisions',
                's',
            )
            ->innerJoin(
                'd.meeting',
                'm',
            )
            ->orderBy('s.sequence');

        if (!$includeAnnulled) {
            // we want to leave out decisions that have been annulled
            $qbn = $this->getEntityManager()->createQueryBuilder();
            $qbn->select('a')
                ->from(
                    Annulment::class,
                    'a',
                )
                ->join(
                    'a.target',
                    'x',
                )
                ->where('x.meeting_type = d.meeting_type')
                ->andWhere('x.meeting_number = d.meeting_number')
                ->andWhere('x.point = d.point')
                ->andWhere('x.number = d.number');
            $qb->andWhere($qb->expr()->not(
                $qb->expr()->exists(
                    $qbn->getDQL(),
                ),
            ));
        }

        // and we want to leave out the decisions that do the annulling
        $qba = $this->getEntityManager()->createQueryBuilder();
        $qba->select('b')
            ->from(
                Annulment::class,
                'b',
            )
            ->where('b.meeting_type = d.meeting_type')
            ->andWhere('b.meeting_number = d.meeting_number')
            ->andWhere('b.decision_point = d.point')
            ->andWhere('b.decision_number = d.number');
        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists(
                $qba->getDQL(),
            ),
        ));

        if (null !== $before) {
            // A decision can only be annulled by a later one; the ledger cannot be rewritten from the past.
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->lt(
                    'm.date',
                    ':before_date',
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq(
                        'm.type',
                        ':before_type',
                    ),
                    $qb->expr()->eq(
                        'm.number',
                        ':before_number',
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->lt(
                            'd.point',
                            ':before_point',
                        ),
                        $qb->expr()->andX(
                            $qb->expr()->eq(
                                'd.point',
                                ':before_point',
                            ),
                            $qb->expr()->lt(
                                'd.number',
                                ':before_decision',
                            ),
                        ),
                    ),
                ),
            ));

            $qb->setParameter(
                ':before_date',
                $before->getDate(),
            );
            $qb->setParameter(
                ':before_type',
                $before->getType(),
            );
            $qb->setParameter(
                ':before_number',
                $before->getNumber(),
            );
            $qb->setParameter(
                ':before_point',
                $beforePoint,
            );
            $qb->setParameter(
                ':before_decision',
                $beforeNumber,
            );
        }

        $qb->setParameter(
            ':search',
            '%' . strtolower($query) . '%',
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Constrain a query to subdecisions whose decision was not annulled.
     *
     * An annulled decision never happened, so neither the thing it brought about nor the thing it took away counts.
     * That cuts both ways for the queries below: an annulled installation is not a current one, and an annulled
     * discharge does not end an installation that is.
     *
     * @param non-empty-string $alias alias of the subdecision to constrain, in $qb.
     */
    private function whereNotAnnulled(
        QueryBuilder $qb,
        string $alias,
    ): void {
        $annulment = 'annulment_' . $alias;
        $target = 'target_' . $alias;

        $qba = $this->getEntityManager()->createQueryBuilder();
        $qba->select($annulment)
            ->from(
                Annulment::class,
                $annulment,
            )
            ->join(
                $annulment . '.target',
                $target,
            )
            ->where($target . '.meeting_type = ' . $alias . '.meeting_type')
            ->andWhere($target . '.meeting_number = ' . $alias . '.meeting_number')
            ->andWhere($target . '.point = ' . $alias . '.decision_point')
            ->andWhere($target . '.number = ' . $alias . '.decision_number');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qba->getDQL()),
        ));
    }

    /**
     * Find current board members.
     *
     * @return BoardInstallation[]
     */
    public function findCurrentBoard(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i, m')
            ->from(
                BoardInstallation::class,
                'i',
            )
            ->join(
                'i.member',
                'm',
            );

        $qbn = $this->getEntityManager()->createQueryBuilder();
        // remove discharges
        $qbn->select('d')
            ->from(
                BoardDischarge::class,
                'd',
            )
            ->join(
                'd.installation',
                'x',
            )
            ->where('x.meeting_type = i.meeting_type')
            ->andWhere('x.meeting_number = i.meeting_number')
            ->andWhere('x.decision_point = i.decision_point')
            ->andWhere('x.decision_number = i.decision_number')
            ->andWhere('x.sequence = i.sequence');

        $this->whereNotAnnulled(
            $qbn,
            'd',
        );
        $this->whereNotAnnulled(
            $qb,
            'i',
        );

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDQL()),
        ));

        return $qb->getQuery()->getResult();
    }

    /**
     * @return BoardInstallation[]
     */
    public function findCurrentBoardNotYetReleased(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('i, m')
            ->from(
                BoardInstallation::class,
                'i',
            )
            ->join(
                'i.member',
                'm',
            );

        // remove discharges
        $qbd = $this->getEntityManager()->createQueryBuilder();
        $qbd->select('d')
            ->from(
                BoardDischarge::class,
                'd',
            )
            ->join(
                'd.installation',
                'x',
            )
            ->where('x.meeting_type = i.meeting_type')
            ->andWhere('x.meeting_number = i.meeting_number')
            ->andWhere('x.decision_point = i.decision_point')
            ->andWhere('x.decision_number = i.decision_number')
            ->andWhere('x.sequence = i.sequence');

        // remove releases
        $qbr = $this->getEntityManager()->createQueryBuilder();
        $qbr->select('r')
            ->from(
                BoardRelease::class,
                'r',
            )
            ->join(
                'r.installation',
                'y',
            )
            ->where('y.meeting_type = i.meeting_type')
            ->andWhere('y.meeting_number = i.meeting_number')
            ->andWhere('y.decision_point = i.decision_point')
            ->andWhere('y.decision_number = i.decision_number')
            ->andWhere('y.sequence = i.sequence');

        $this->whereNotAnnulled(
            $qbd,
            'd',
        );
        $this->whereNotAnnulled(
            $qbr,
            'r',
        );
        $this->whereNotAnnulled(
            $qb,
            'i',
        );

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbd->getDQL()),
        ));
        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbr->getDQL()),
        ));

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all currently granted key codes.
     *
     * @return KeyGranting[]
     */
    public function findCurrentKeys(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('g, m')
            ->from(
                KeyGranting::class,
                'g',
            )
            ->join(
                'g.member',
                'm',
            )
            ->where('g.until >= :now');

        // remove withdrawals
        $qbn = $this->getEntityManager()->createQueryBuilder();
        $qbn->select('d')
            ->from(
                KeyWithdrawal::class,
                'd',
            )
            ->join(
                'd.granting',
                'x',
            )
            ->where('x.meeting_type = g.meeting_type')
            ->andWhere('x.meeting_number = g.meeting_number')
            ->andWhere('x.decision_point = g.decision_point')
            ->andWhere('x.decision_number = g.decision_number')
            ->andWhere('x.sequence = g.sequence');

        $this->whereNotAnnulled(
            $qbn,
            'd',
        );
        $this->whereNotAnnulled(
            $qb,
            'g',
        );

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDQL()),
        ));

        $qb->setParameter(
            'now',
            new DateTime('now'),
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the subdecisions of the given type that reference the given subdecision.
     *
     * The references are deliberately looked up here instead of through the inverse side of the association: an
     * installation can carry more than one discharge once an earlier one has been annulled, and the to-one inverse
     * sides silently return only the first of those.
     *
     * @template T of SubDecision
     *
     * @param class-string<T>  $type
     * @param non-empty-string $property
     *
     * @return T[]
     */
    public function findReferencingSubDecisions(
        string $type,
        string $property,
        Decision|SubDecision $referenced,
    ): array {
        return $this->getEntityManager()->getRepository($type)->findBy([$property => $referenced]);
    }

    /**
     * Delete a decision.
     */
    public function deleteDecision(
        MeetingTypes $type,
        int $number,
        int $point,
        int $decision,
    ): void {
        $decision = $this->findDecision(
            $type,
            $number,
            $point,
            $decision,
        );

        $this->getEntityManager()->remove($decision);
        $this->getEntityManager()->flush();
    }

    /**
     * Persist a meeting model.
     */
    public function persist(Meeting $meeting): void
    {
        $this->getEntityManager()->persist($meeting);
        $this->getEntityManager()->flush();
    }
}
