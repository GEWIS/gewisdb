<?php

namespace Checker\Mapper;

use Doctrine\ORM\EntityManager;

class Budget
{
    use Filter;

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
     * Returns all budgets thar are created at $meeting
     *
     * @param \Database\Model\Meeting $meeting
     * @return array \Database\Model\Subdecision\Budget Array of all budgets created at $meeting
     */
    public function getAllBudgets(\Database\Model\Meeting $meeting)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('b')
            ->where('m.date = :meeting_date')
            ->andWhere('m.type = :meeting_type')
            ->from('Database\Model\SubDecision\Budget', 'b')
            ->innerJoin('b.decision', 'dec')
            ->innerJoin('dec.meeting', 'm')
            ->setParameter('meeting_date', $meeting->getDate()->format('Y-m-d'))
            ->setParameter('meeting_type', $meeting->getType());

        return $this->filterDeleted($qb->getQuery()->getResult());
    }
}
