<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\SavedQuery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<SavedQuery>
 */
class SavedQueryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            SavedQuery::class,
        );
    }

    /**
     * Persist a query.
     */
    public function persist(SavedQuery $query): void
    {
        $this->getEntityManager()->persist($query);
        $this->getEntityManager()->flush();
    }

    /**
     * Find by name.
     */
    public function findByName(string $name): ?SavedQuery
    {
        $qb = $this->createQueryBuilder('q');
        $qb->where('LOWER(q.name) LIKE LOWER(:name)')
           ->setMaxResults(1)
           ->setParameter(
               'name',
               $name,
           );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all.
     *
     * @return list<SavedQuery>
     */
    #[Override]
    public function findAll(): array
    {
        $qb = $this->createQueryBuilder('q');
        // ORM 3 dropped QueryBuilder::add(). Passing both expressions as a single sort argument keeps the
        // emitted `ORDER BY lower(q.category), lower(q.name) ASC` byte-for-byte identical; splitting them over
        // orderBy()/addOrderBy() would add an explicit ASC to the first term.
        $qb->orderBy(
            'lower(q.category), lower(q.name)',
            'ASC',
        );

        return $qb->getQuery()->getResult();
    }
}
