<?php

namespace Database\Mapper;

use Database\Model\SavedQuery as SavedQueryModel;
use Doctrine\ORM\{
    EntityManager,
    EntityRepository,
};

/**
 * Installation Function mapper
 */
class SavedQuery
{
    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Persist a query.
     */
    public function persist(SavedQueryModel $query): void
    {
        $this->em->persist($query);
        $this->em->flush();
    }

    /**
     * Find.
     */
    public function find(int $id): ?SavedQueryModel
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Find all.
     *
     * @return array<array-key, SavedQueryModel>
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
        return $this->em->getRepository(SavedQueryModel::class);
    }
}
