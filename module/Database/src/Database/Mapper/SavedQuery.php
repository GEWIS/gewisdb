<?php

namespace Database\Mapper;

use Database\Model\SavedQuery as QueryModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

/**
 * Installation Function mapper
 */
class SavedQuery
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
     * Persist a query.
     *
     * @param QueryModel $function Query to persist.
     */
    public function persist(QueryModel $query)
    {
        $this->em->persist($query);
        $this->em->flush();
    }

    /**
     * Find.
     * @param string $id
     * @return QueryModel
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Find all.
     * @return array of QueryModel's
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Database\Model\SavedQuery');
    }

}
