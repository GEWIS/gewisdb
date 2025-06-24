<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\MailmanMailingList as MailmanMailingListModel;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mailman Mailing List mapper.
 */
class MailmanMailingList
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Persist a mailman list.
     */
    public function persist(MailmanMailingListModel $list): void
    {
        $this->em->persist($list);
        $this->em->flush();
    }

    /**
     * Remove a mailman list.
     */
    public function remove(MailmanMailingListModel $list): void
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
     * Find active mailing lists (i.e. seen in the last 1 day).
     *
     * @return array<array-key, MailmanMailingListModel>
     */
    public function findActive(): array
    {
        $lastFetch = $this->getLastFetchTime();

        $qb = $this->em->createQueryBuilder();

        $qb->select('l')
            ->from(MailmanMailingListModel::class, 'l')
            ->where('l.lastSeen >= :lastSeen');

        $qb->setParameter('lastSeen', $lastFetch?->sub(new DateInterval('P1D')));

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all.
     *
     * @return array<array-key, MailmanMailingListModel>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findBy([], ['id' => 'ASC']);
    }

    /**
     * Find a list by mailman ID.
     */
    public function find(string $mailmanId): ?MailmanMailingListModel
    {
        return $this->getRepository()->find($mailmanId);
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository('Database\Model\MailmanMailingList');
    }
}
