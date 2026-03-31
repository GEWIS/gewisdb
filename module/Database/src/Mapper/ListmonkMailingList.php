<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\ListmonkMailingList as ListmonkMailingListModel;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Listmonk Mailing List mapper.
 */
class ListmonkMailingList
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Persist a listmonk list.
     */
    public function persist(ListmonkMailingListModel $list): void
    {
        $this->em->persist($list);
        $this->em->flush();
    }

    /**
     * Remove a listmonk list.
     */
    public function remove(ListmonkMailingListModel $list): void
    {
        $this->em->remove($list);
        $this->em->flush();
    }

    /**
     * Get the time of last sync, or null if none
     */
    public function getLastFetchTime(): ?DateTime
    {
        $list = $this->getRepository()->findOneBy([], ['lastSeen' => 'DESC']);

        return $list?->getLastSeen();
    }

    /**
     * Find active mailing lists (i.e. seen in the last fetch or the hour before)
     *
     * @return array<array-key, ListmonkMailingListModel>
     */
    public function findActive(): array
    {
        $lastFetch = $this->getLastFetchTime();

        $qb = $this->em->createQueryBuilder();

        $qb->select('l')
            ->from(ListmonkMailingListModel::class, 'l')
            ->where('l.lastSeen >= :lastSeen');

        $qb->setParameter('lastSeen', $lastFetch?->sub(new DateInterval('PT1H5M')));

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all.
     *
     * @return array<array-key, ListmonkMailingListModel>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findBy([], ['id' => 'ASC']);
    }

    /**
     * Find a list by listmonk ID (UUID).
     */
    public function find(string $listmonkId): ?ListmonkMailingListModel
    {
        return $this->getRepository()->find($listmonkId);
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(ListmonkMailingListModel::class);
    }
}