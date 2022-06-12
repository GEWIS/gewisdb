<?php

namespace Database\Mapper;

use Database\Model\InstallationFunction as FunctionModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

/**
 * Installation Function mapper
 */
class InstallationFunction
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
     * Persist a function.
     *
     * @param FunctionModel $function Function to persist.
     */
    public function persist(FunctionModel $function)
    {
        $this->em->persist($function);
        $this->em->flush();
    }

    /**
     * Find all.
     * @return array of FunctionModel's
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
        return $this->em->getRepository('Database\Model\InstallationFunction');
    }
}
