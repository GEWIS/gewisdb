<?php

namespace Database\Mapper;

use Database\Model\Meeting as MeetingModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

class Meeting
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;


    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Check if a model is managed.
     *
     * @param MeetingModel $meeting
     *
     * @return boolean if managed
     */
    public function isManaged(MeetingModel $meeting)
    {
        return $this->em->getUnitOfWork()->isInIdentityMap($meeting);
    }

    /**
     * Find all meetings.
     *
     * Also counts all decision per meeting.
     *
     * @return array All meetings.
     */
    public function findAll()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, COUNT(d)')
            ->from('Database\Model\Meeting', 'm')
            ->leftJoin('m.decisions', 'd')
            ->groupBy('m')
            ->orderBy('m.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find decisions by given meetings.
     *
     * @param array $meetings
     *
     * @return array All decisions.
     */
    public function findDecisionsByMeetings($meetings)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('d, s')
            ->from('Database\Model\Decision', 'd')
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
                $qb->expr()->eq('m.number', ':number' . $num)
            ));
            $qb->setParameter(':type' . $num, $meeting['type']);
            $qb->setParameter(':number' . $num, $meeting['number']);
            $num++;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a meeting with all decisions.
     *
     * @param string $type
     * @param int $number
     *
     * @return MeetingModel
     */
    public function find($type, $number)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, d, s')
            ->from('Database\Model\Meeting', 'm')
            ->where('m.type = :type')
            ->andWhere('m.number = :number')
            ->leftJoin('m.decisions', 'd')
            ->leftJoin('d.subdecisions', 's')
            ->orderBy('d.point')
            ->addOrderBy('d.number')
            ->addOrderBy('s.number');

        $qb->setParameter(':type', $type);
        $qb->setParameter(':number', $number);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Delete a decision.
     *
     * @param string $type
     * @param int $number
     * @param int $point
     * @param int $decision
     *
     * @return \Database\Model\Decision
     */
    public function findDecision($type, $number, $point, $decision)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('d, s')
            ->from('Database\Model\Decision', 'd')
            ->where('d.meeting_type = :type')
            ->andWhere('d.meeting_number = :number')
            ->andWhere('d.point = :point')
            ->andWhere('d.number = :decision')
            ->leftJoin('d.subdecisions', 's')
            ->orderBy('s.number');

        $qb->setParameter(':type', $type);
        $qb->setParameter(':number', $number);
        $qb->setParameter(':point', $point);
        $qb->setParameter(':decision', $decision);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Search for a decision.
     *
     * @param string $query
     *
     * @return array of decisions.
     */
    public function searchDecision($query)
    {
        $qb = $this->em->createQueryBuilder();

        $fields = array();
        $fields[] = 'LOWER(d.meeting_type)';
        $fields[] = "' '";
        $fields[] = 'd.meeting_number';
        $fields[] = "'.'";
        $fields[] = 'd.point';
        $fields[] = "'.'";
        $fields[] = 'd.number';
        $fields[] = "' '";
        $fields = implode(', ', $fields);
        $fields = "CONCAT($fields)";


        $qb->select('d, s, m')
            ->from('Database\Model\Decision', 'd')
            ->where("$fields LIKE :search")
            ->leftJoin('d.subdecisions', 's')
            ->innerJoin('d.meeting', 'm')
            ->orderBy('s.number');

        $qb->setParameter(':search', '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
    }

    /**
     * Delete a decision.
     *
     * @param string $type
     * @param int $number
     * @param int $point
     * @param int $decision
     */
    public function deleteDecision($type, $number, $point, $decision)
    {
        $decision = $this->findDecision($type, $number, $point, $decision);

        $this->em->remove($decision);
        $this->em->flush();
    }

    /**
     * Persist a meeting model.
     *
     * @param MeetingModel $meeting Meeting to persist.
     */
    public function persist($meeting)
    {
        $this->em->persist($meeting);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Database\Model\Meeting');
    }

}
