<?php

declare(strict_types=1);

namespace Database\Mapper;

use Application\Model\Enums\MeetingTypes;
use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Board\Discharge as BoardDischargeModel;
use Database\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Database\Model\SubDecision\Board\Release as BoardReleaseModel;
use Database\Model\SubDecision\Destroy as DestroyModel;
use Database\Model\SubDecision\Key\Granting as KeyGrantingModel;
use Database\Model\SubDecision\Key\Withdrawal as KeyWithdrawalModel;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

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

            $qb->addOrderBy('virtSort', 'ASC');

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
            ->addOrderBy('s.number', 'ASC');

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
            ->leftJoin('d.destroyedby', 'db')
            ->orderBy('d.point')
            ->addOrderBy('d.number')
            ->addOrderBy('s.number');

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
            ->orderBy('s.number');

        $qb->setParameter(':meeting_type', $meetingType);
        $qb->setParameter(':meeting_number', $meetingNumber);
        $qb->setParameter(':decision_point', $decisionPoint);
        $qb->setParameter(':decision_number', $decisionNumber);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Search for a decision.
     *
     * @return DecisionModel[]
     */
    public function searchDecision(
        string $query,
        bool $includeDestroyed = false,
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
            ->orderBy('s.number');

        if (!$includeDestroyed) {
            // we want to leave out decisions that have been destroyed
            $qbn = $this->em->createQueryBuilder();
            $qbn->select('a')
                ->from(DestroyModel::class, 'a')
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

        $qb->setParameter(':search', '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
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
            ->andWhere('x.number = i.number');

        // TODO: destroyed decisions (both ways!)
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
            ->andWhere('x.number = i.number');

        // remove releases
        $qbr = $this->em->createQueryBuilder();
        $qbr->select('r')
            ->from(BoardReleaseModel::class, 'r')
            ->join('r.installation', 'y')
            ->where('y.meeting_type = i.meeting_type')
            ->andWhere('y.meeting_number = i.meeting_number')
            ->andWhere('y.decision_point = i.decision_point')
            ->andWhere('y.decision_number = i.decision_number')
            ->andWhere('y.number = i.number');

        // TODO: destroyed decisions (both ways!)
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
            ->andWhere('x.number = g.number');

        // TODO: destroyed decisions (both ways!)
        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDql()),
        ));

        $qb->setParameter('now', new DateTime('now'));

        return $qb->getQuery()->getResult();
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
