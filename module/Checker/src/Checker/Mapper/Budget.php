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
     * Returns all budgets that have been created  before meeting $meeting
     * @param $meeting
     */
    public function getAllBudgets($meeting)
    {
            $qb = $this->em->createQueryBuilder();

            $qb->select('b')
                ->where('b.meeting_number <= :meeting_number')
                ->from('Database\Model\SubDecision\Budget', 'b')
                ->setParameter('meeting_number', $meeting);

            // TODO: minus deleted budgets
            return $qb->getQuery()->getResult();
    }
} 