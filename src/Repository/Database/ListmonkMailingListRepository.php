<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\ListmonkMailingList;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<ListmonkMailingList>
 */
class ListmonkMailingListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ListmonkMailingList::class,
        );
    }

    /**
     * Persist a listmonk list.
     */
    public function persist(ListmonkMailingList $list): void
    {
        $this->getEntityManager()->persist($list);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove a listmonk list.
     */
    public function remove(ListmonkMailingList $list): void
    {
        $this->getEntityManager()->remove($list);
        $this->getEntityManager()->flush();
    }

    /**
     * Get the time of last sync, or null if none
     */
    public function getLastFetchTime(): ?DateTime
    {
        $list = $this->findOneBy(
            [],
            ['lastSeen' => 'DESC'],
        );

        return $list?->getLastSeen();
    }

    /**
     * Find active mailing lists (i.e. seen in the last fetch or the hour before)
     *
     * @return array<array-key, ListmonkMailingList>
     */
    public function findActive(): array
    {
        $lastFetch = $this->getLastFetchTime();

        $qb = $this->createQueryBuilder('l');

        $qb->where('l.lastSeen >= :lastSeen');

        $qb->setParameter(
            'lastSeen',
            null !== $lastFetch ? (clone $lastFetch)->sub(new DateInterval('PT1H5M')) : null,
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all.
     *
     * @return list<ListmonkMailingList>
     */
    #[Override]
    public function findAll(): array
    {
        return $this->findBy(
            [],
            ['id' => 'ASC'],
        );
    }
}
