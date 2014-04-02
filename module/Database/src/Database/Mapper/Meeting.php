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
            ->groupBy('m');

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
     * Search for organ decisions.
     *
     * @param string $query
     *
     * @return array Organs
     */
    public function organSearch($query)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('o, d, m')
            ->from('Database\Model\SubDecision\Foundation', 'o')
            ->where('LOWER(o.name) LIKE :name')
            ->orWhere('LOWER(o.abbr) LIKE :name')
            ->join('o.decision', 'd')
            ->join('d.meeting', 'm');

        $qb->setParameter(':name', '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
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
