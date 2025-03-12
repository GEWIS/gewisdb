<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\SavedQuery as SavedQueryModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Installation Function mapper
 */
class SavedQuery
{
    public function __construct(protected readonly EntityManager $em)
    {
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
     * Find by name.
     */
    public function findByName(string $name): ?SavedQueryModel
    {
        $qb = $this->getRepository()->createQueryBuilder('q');
        $qb->where('LOWER(q.name) LIKE LOWER(:name)')
           ->setMaxResults(1)
           ->setParameter('name', $name);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all.
     *
     * @return array<array-key, SavedQueryModel>
     */
    public function findAll(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('q');
        $qb->add('orderBy', 'lower(q.category), lower(q.name) ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(SavedQueryModel::class);
    }
}
