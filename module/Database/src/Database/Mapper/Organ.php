<?php

namespace Database\Mapper;

use Database\Model\SubDecision\Foundation;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

class Organ
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
     * Find an organ. Also calculate which are it's current members.
     *
     * @param string $type
     * @param string $meetingNumber
     * @param string $decisionPoint
     * @param string $decisionNumber
     * @param string $subdecisionNumber
     *
     * @return Foundation
     */
    public function find($type, $meetingNumber, $decisionPoint, $decisionNumber, $subdecisionNumber)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('o', 'r')
            ->from('Database\Model\SubDecision\Foundation', 'o')
            ->where('o.meeting_type = :type')
            ->andWhere('o.meeting_number = :meeting_number')
            ->andWhere('o.decision_point = :decision_point')
            ->andWhere('o.decision_number = :decision_number')
            ->andWhere('o.number = :number')
            ->leftJoin('o.references', 'r')
            ->andWhere('r INSTANCE OF Database\Model\SubDecision\Installation');

        // discharges
        $qbn = $this->em->createQueryBuilder();
        $qbn->select('d')
            ->from('Database\Model\SubDecision\Discharge', 'd')
            ->join('d.installation', 'x')
            ->where('x.meeting_type = r.meeting_type')
            ->andWhere('x.meeting_number = r.meeting_number')
            ->andWhere('x.decision_point = r.decision_point')
            ->andWhere('x.decision_number = r.decision_number')
            ->andWhere('x.number = r.number');

        // destroyed discharge decisions
        $qbnd = $this->em->createQueryBuilder();
        $qbnd->select('b')
            ->from('Database\Model\SubDecision\Destroy', 'b')
            ->join('b.target', 'z')
            ->where('z.meeting_type = d.meeting_type')
            ->andWhere('z.meeting_number = d.meeting_number')
            ->andWhere('z.point = d.decision_point')
            ->andWhere('z.number = d.decision_number');

        $qbn->andWhere($qbn->expr()->not(
            $qbn->expr()->exists($qbnd->getDql())
        ));

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDql())
        ));

        // destroyed installation decisions
        $qbd = $this->em->createQueryBuilder();
        $qbd->select('a')
            ->from('Database\Model\SubDecision\Destroy', 'a')
            ->join('a.target', 'y')
            ->where('y.meeting_type = r.meeting_type')
            ->andWhere('y.meeting_number = r.meeting_number')
            ->andWhere('y.point = r.decision_point')
            ->andWhere('y.number = r.decision_number');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbd->getDql())
        ));

        $qb->setParameter(':type', $type);
        $qb->setParameter(':meeting_number', $meetingNumber);
        $qb->setParameter(':decision_point', $decisionPoint);
        $qb->setParameter(':decision_number', $decisionNumber);
        $qb->setParameter(':number', $subdecisionNumber);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Search for organ decisions.
     *
     * This is a really complicated query, we might want to create a
     * materialized view for organs, with a field if they are abrogated or not.
     *
     * And since events are implemented anyways, we might want to use that to
     * automatically process changes.
     *
     * @param string $query
     * @param boolean $includeAbrogated
     *
     * @return array Organs
     */
    public function organSearch($query, $includeAbrogated = false)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('o, d, m')
            ->from('Database\Model\SubDecision\Foundation', 'o')
            ->where('LOWER(o.name) LIKE :name')
            ->orWhere('LOWER(o.abbr) LIKE :name')
            ->join('o.decision', 'd')
            ->join('d.meeting', 'm');

        if (!$includeAbrogated) {
            // we want to leave out organs that have been abrogated
            $qbn = $this->em->createQueryBuilder();
            $qbn->select('a')
                ->from('Database\Model\SubDecision\Abrogation', 'a')
                ->join('a.foundation', 'x')
                ->where('x.meeting_type = o.meeting_type')
                ->andWhere('x.meeting_number = o.meeting_number')
                ->andWhere('x.decision_point = o.decision_point')
                ->andWhere('x.decision_number = o.decision_number')
                ->andWhere('x.number = o.number');
            // add the subexpression
            $qb->andWhere($qb->expr()->not(
                $qb->expr()->exists(
                    $qbn->getDql()
                )
            ));
        }

        $qb->setParameter(':name', '%' . strtolower($query) . '%');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Database\Model\SubDecision\Foundation');
    }

}
