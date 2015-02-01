<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 26-1-15
 * Time: 16:01
 */

namespace Checker\Mapper;

use Doctrine\ORM\EntityManager;

class Budget {
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
     * Returns all budgets that have been created at meeting $meeting
     * @param $meeting
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

        // TODO: minus deleted decision
        return $qb->getQuery()->getResult();
    }
} 