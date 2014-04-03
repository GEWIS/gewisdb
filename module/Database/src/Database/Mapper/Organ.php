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
     * Search for organ decisions.
     *
     * This is a really complicated query, we might want to create a
     * materialized view for organs, with a field if they are abrogated or not.
     *
     * And since events are implemented anyways, we might want to use that to
     * automatically process changes.
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

        // we want to leave out organs that have been abrogated
        $qbn = $this->em->createQueryBuilder();
        $qbn->select('a')
            ->from('Database\Model\SubDecision\Abrogation', 'a')
            ->join('a.foundation', 'x')
            ->where('x.meeting_type = o.meeting_type')
            ->andWhere('x.meeting_number = o.meeting_number')
            ->andWhere('x.decision_point = o.decision_point')
            ->andWhere('x.decision_number = o.decision_number');
        // add the subexpression
        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists(
                $qbn->getDql()
            )
        ));

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
