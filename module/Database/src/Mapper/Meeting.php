<?php

declare(strict_types=1);

namespace Database\Mapper;

use Application\Model\Enums\MeetingTypes;
use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision as SubDecisionModel;
use Database\Model\SubDecision\Annulment as AnnulmentModel;
use Database\Model\SubDecision\Board\Discharge as BoardDischargeModel;
use Database\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Database\Model\SubDecision\Board\Release as BoardReleaseModel;
use Database\Model\SubDecision\Key\Granting as KeyGrantingModel;
use Database\Model\SubDecision\Key\Withdrawal as KeyWithdrawalModel;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use function implode;
use function str_replace;
use function strtolower;

class Meeting
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Check if a model is managed.
     */
    public function isManaged(MeetingModel $meeting): bool
    {
        return $this->em->getUnitOfWork()->isInIdentityMap($meeting);
    }

    /**
     * Search for a meeting.
     *
     * @return MeetingModel[]
     */
    public function searchMeeting(
        string $query,
    ): array {
        $qb = $this->em->createQueryBuilder();

        $fields = [];
        $fields[] = 'LOWER(m.type)';
        $fields[] = 'm.number';
        $fields = implode(', ', $fields);
        $fields = 'CONCAT(' . $fields . ')';

        $qb->select('m')
            ->from(MeetingModel::class, 'm')
            ->where($fields . ' LIKE :search')
            ->orWhere('CONCAT(m.number, \'\') LIKE :search')
            ->orderBy('m.date', 'DESC');

        $qb->setParameter(':search', str_replace(' ', '', strtolower($query)) . '%');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all meetings. Also counts all decision per meeting.
     *
     * @return array<array-key, array{0: MeetingModel, 1: int}>
     */
    public function findAll(
        bool $count = true,
        bool $asc = false,
    ): array {
        if ($count) {
            $qb = $this->em->createQueryBuilder();

            $qb->select('m, COUNT(d)')
                ->addSelect('(CASE WHEN m.type = :virtual_meeting THEN 1 ELSE 0 END) AS HIDDEN virtSort')
                ->from(MeetingModel::class, 'm')
                ->leftJoin('m.decisions', 'd')
                ->groupBy('m')
                ->setParameter(':virtual_meeting', MeetingTypes::VIRT);

            if ($asc) {
                $qb->addOrderBy('m.date', 'ASC');
            } else {
                $qb->addOrderBy('m.date', 'DESC');
            }

            // A meeting held to put right what an earlier one got wrong is a virtual one on that same date, so it goes
            // last of that date. Beyond that the date says nothing about which of two meetings came first, and a
            // replay has to make the same choice every time, so type and number settle the rest.
            $qb->addOrderBy('virtSort', 'ASC')
                ->addOrderBy('m.type', 'ASC')
                ->addOrderBy('m.number', 'ASC');

            return $qb->getQuery()->getResult();
        }

        return $this->getRepository()->findAll();
    }

    /**
     * Find the last meeting.
     */
    public function findLast(): ?MeetingModel
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m')
            ->from(MeetingModel::class, 'm')
            ->leftJoin('m.decisions', 'd')
            ->orderBy('m.date', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find decisions by given meetings.
     *
     * @param array<array-key, array{type: string, number: int}> $meetings
     *
     * @return DecisionModel[]
     */
    public function findDecisionsByMeetings(array $meetings): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('d, s')
            ->from(DecisionModel::class, 'd')
            ->join('d.meeting', 'm')
            ->leftJoin('d.subdecisions', 's')
            ->orderBy('m.type', 'ASC')
            ->addOrderBy('m.number', 'ASC')
            ->addOrderBy('d.point', 'ASC')
            ->addOrderBy('d.number', 'ASC')
            ->addOrderBy('s.sequence', 'ASC');

        $num = 0;
        foreach ($meetings as $meeting) {
            $qb->orWhere($qb->expr()->andX(
                $qb->expr()->eq('m.type', ':type' . $num),
                $qb->expr()->eq('m.number', ':number' . $num),
            ));
            $qb->setParameter(':type' . $num, $meeting['type']);
            $qb->setParameter(':number' . $num, $meeting['number']);
            $num++;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a meeting with all decisions.
     */
    public function find(
        MeetingTypes $type,
        int $number,
    ): ?MeetingModel {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, d, s, db')
            ->from(MeetingModel::class, 'm')
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->leftJoin('m.decisions', 'd')
            ->leftJoin('d.subdecisions', 's')
            ->leftJoin('d.annulledBy', 'db')
            ->orderBy('d.point')
            ->addOrderBy('d.number')
            ->addOrderBy('s.sequence');

        $qb->setParameter(':type', $type);
        $qb->setParameter(':number', $number);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Find a decision.
     */
    public function findDecision(
        MeetingTypes $meetingType,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
    ): ?DecisionModel {
        $qb = $this->em->createQueryBuilder();

        $qb->select('d, s')
            ->from(DecisionModel::class, 'd')
            ->where('d.meeting_type = :meeting_type')
            ->andWhere('d.meeting_number = :meeting_number')
            ->andWhere('d.point = :decision_point')
            ->andWhere('d.number = :decision_number')
            ->leftJoin('d.subdecisions', 's')
            ->orderBy('s.sequence');

        $qb->setParameter(':meeting_type', $meetingType);
        $qb->setParameter(':meeting_number', $meetingNumber);
        $qb->setParameter(':decision_point', $decisionPoint);
        $qb->setParameter(':decision_number', $decisionNumber);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Search for a decision.
     *
     * Decisions that annul another decision are never returned: annulling an annulment has no well-defined meaning,
     * because an annulment has no effects of its own to revert.
     *
     * @param MeetingModel|null $before when given, only decisions taken before this meeting are returned, and within
     *                                  that meeting only those before $beforePoint and $beforeNumber.
     *
     * @return DecisionModel[]
     */
    public function searchDecision(
        string $query,
        bool $includeAnnulled = false,
        ?MeetingModel $before = null,
        ?int $beforePoint = null,
        ?int $beforeNumber = null,
    ): array {
        $qb = $this->em->createQueryBuilder();

        $fields = [];
        $fields[] = 'LOWER(d.meeting_type)';
        $fields[] = "' '";
        $fields[] = 'd.meeting_number';
        $fields[] = "'.'";
        $fields[] = 'd.point';
        $fields[] = "'.'";
        $fields[] = 'd.number';
        $fields[] = "' '";
        $fields = implode(', ', $fields);
        $fields = 'CONCAT(' . $fields . ')';

        $qb->select('d, s, m')
            ->from(DecisionModel::class, 'd')
            ->where($fields . ' LIKE :search')
            ->leftJoin('d.subdecisions', 's')
            ->innerJoin('d.meeting', 'm')
            ->orderBy('s.sequence');

        if (!$includeAnnulled) {
            // we want to leave out decisions that have been annulled
            $qbn = $this->em->createQueryBuilder();
            $qbn->select('a')
                ->from(AnnulmentModel::class, 'a')
                ->join('a.target', 'x')
                ->where('x.meeting_type = d.meeting_type')
                ->andWhere('x.meeting_number = d.meeting_number')
                ->andWhere('x.point = d.point')
                ->andWhere('x.number = d.number');
            $qb->andWhere($qb->expr()->not(
                $qb->expr()->exists(
                    $qbn->getDql(),
                ),
            ));
        }

        // and we want to leave out the decisions that do the annulling
        $qba = $this->em->createQueryBuilder();
        $qba->select('b')
            ->from(AnnulmentModel::class, 'b')
            ->where('b.meeting_type = d.meeting_type')
            ->andWhere('b.meeting_number = d.meeting_number')
            ->andWhere('b.decision_point = d.point')
            ->andWhere('b.decision_number = d.number');
        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists(
                $qba->getDql(),
            ),
        ));

        if (null !== $before) {
            // A decision can only be annulled by a later one; the ledger cannot be rewritten from the past.
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->lt('m.date', ':before_date'),
                $qb->expr()->andX(
                    $qb->expr()->eq('m.type', ':before_type'),
                    $qb->expr()->eq('m.number', ':before_number'),
                    $qb->expr()->orX(
                        $qb->expr()->lt('d.point', ':before_point'),
                        $qb->expr()->andX(
                            $qb->expr()->eq('d.point', ':before_point'),
                            $qb->expr()->lt('d.number', ':before_decision'),
                        ),
                    ),
                ),
            ));

            $qb->setParameter(':before_date', $before->getDate());
            $qb->setParameter(':before_type', $before->getType());
            $qb->setParameter(':before_number', $before->getNumber());
            $qb->setParameter(':before_point', $beforePoint);
            $qb->setParameter(':before_decision', $beforeNumber);
        }

        $qb->setParameter(':search', '%' . strtolower($query) . '%');

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

        $qba = $this->em->createQueryBuilder();
        $qba->select($annulment)
            ->from(AnnulmentModel::class, $annulment)
            ->join($annulment . '.target', $target)
            ->where($target . '.meeting_type = ' . $alias . '.meeting_type')
            ->andWhere($target . '.meeting_number = ' . $alias . '.meeting_number')
            ->andWhere($target . '.point = ' . $alias . '.decision_point')
            ->andWhere($target . '.number = ' . $alias . '.decision_number');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qba->getDql()),
        ));
    }

    /**
     * Find current board members.
     *
     * @return BoardInstallationModel[]
     */
    public function findCurrentBoard(): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('i, m')
            ->from(BoardInstallationModel::class, 'i')
            ->join('i.member', 'm');

        $qbn = $this->em->createQueryBuilder();
        // remove discharges
        $qbn->select('d')
            ->from(BoardDischargeModel::class, 'd')
            ->join('d.installation', 'x')
            ->where('x.meeting_type = i.meeting_type')
            ->andWhere('x.meeting_number = i.meeting_number')
            ->andWhere('x.decision_point = i.decision_point')
            ->andWhere('x.decision_number = i.decision_number')
            ->andWhere('x.sequence = i.sequence');

        $this->whereNotAnnulled($qbn, 'd');
        $this->whereNotAnnulled($qb, 'i');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDql()),
        ));

        return $qb->getQuery()->getResult();
    }

    /**
     * @return BoardInstallationModel[]
     */
    public function findCurrentBoardNotYetReleased(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('i, m')
            ->from(BoardInstallationModel::class, 'i')
            ->join('i.member', 'm');

        // remove discharges
        $qbd = $this->em->createQueryBuilder();
        $qbd->select('d')
            ->from(BoardDischargeModel::class, 'd')
            ->join('d.installation', 'x')
            ->where('x.meeting_type = i.meeting_type')
            ->andWhere('x.meeting_number = i.meeting_number')
            ->andWhere('x.decision_point = i.decision_point')
            ->andWhere('x.decision_number = i.decision_number')
            ->andWhere('x.sequence = i.sequence');

        // remove releases
        $qbr = $this->em->createQueryBuilder();
        $qbr->select('r')
            ->from(BoardReleaseModel::class, 'r')
            ->join('r.installation', 'y')
            ->where('y.meeting_type = i.meeting_type')
            ->andWhere('y.meeting_number = i.meeting_number')
            ->andWhere('y.decision_point = i.decision_point')
            ->andWhere('y.decision_number = i.decision_number')
            ->andWhere('y.sequence = i.sequence');

        $this->whereNotAnnulled($qbd, 'd');
        $this->whereNotAnnulled($qbr, 'r');
        $this->whereNotAnnulled($qb, 'i');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbd->getDql()),
        ));
        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbr->getDql()),
        ));

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all currently granted key codes.
     *
     * @return KeyGrantingModel[]
     */
    public function findCurrentKeys(): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('g, m')
            ->from(KeyGrantingModel::class, 'g')
            ->join('g.member', 'm')
            ->where('g.until >= :now');

        // remove withdrawals
        $qbn = $this->em->createQueryBuilder();
        $qbn->select('d')
            ->from(KeyWithdrawalModel::class, 'd')
            ->join('d.granting', 'x')
            ->where('x.meeting_type = g.meeting_type')
            ->andWhere('x.meeting_number = g.meeting_number')
            ->andWhere('x.decision_point = g.decision_point')
            ->andWhere('x.decision_number = g.decision_number')
            ->andWhere('x.sequence = g.sequence');

        $this->whereNotAnnulled($qbn, 'd');
        $this->whereNotAnnulled($qb, 'g');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDql()),
        ));

        $qb->setParameter('now', new DateTime('now'));

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the subdecisions of the given type that reference the given subdecision.
     *
     * The references are deliberately looked up here instead of through the inverse side of the association: an
     * installation can carry more than one discharge once an earlier one has been annulled, and the to-one inverse
     * sides silently return only the first of those.
     *
     * @template T of SubDecisionModel
     *
     * @param class-string<T>  $type
     * @param non-empty-string $property
     *
     * @return T[]
     */
    public function findReferencingSubDecisions(
        string $type,
        string $property,
        DecisionModel|SubDecisionModel $referenced,
    ): array {
        return $this->em->getRepository($type)->findBy([$property => $referenced]);
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
        $decision = $this->findDecision($type, $number, $point, $decision);

        $this->em->remove($decision);
        $this->em->flush();
    }

    /**
     * Persist a meeting model.
     */
    public function persist(MeetingModel $meeting): void
    {
        $this->em->persist($meeting);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(MeetingModel::class);
    }
}
