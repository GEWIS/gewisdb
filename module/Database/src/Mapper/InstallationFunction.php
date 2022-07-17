<?php

namespace Database\Mapper;

use Database\Model\InstallationFunction as InstallationFunctionModel;
use Doctrine\ORM\{
    EntityManager,
    EntityRepository,
};

/**
 * Installation Function mapper
 */
class InstallationFunction
{
    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Persist a function.
     */
    public function persist(InstallationFunctionModel $function): void
    {
        $this->em->persist($function);
        $this->em->flush();
    }

    /**
     * Find all.
     *
     * @return array<array-key, InstallationFunctionModel>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(InstallationFunctionModel::class);
    }
}
