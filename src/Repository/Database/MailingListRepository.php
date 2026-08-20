<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\MailingList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<MailingList>
 */
class MailingListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MailingList::class,
        );
    }

    /**
     * Persist a list.
     */
    public function persist(MailingList $list): void
    {
        $this->getEntityManager()->persist($list);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove a list.
     */
    public function remove(MailingList $list): void
    {
        $this->getEntityManager()->remove($list);
        $this->getEntityManager()->flush();
    }

    /**
     * Find all.
     *
     * @return list<MailingList>
     */
    #[Override]
    public function findAll(): array
    {
        return $this->findBy(
            [],
            ['name' => 'ASC'],
        );
    }

    /**
     * Find all mailing lists that are on the subscription form.
     *
     * @return array<array-key, MailingList>
     */
    public function findAllOnForm(): array
    {
        return $this->findBy(
            ['onForm' => true],
            ['name' => 'ASC'],
        );
    }

    /**
     * Find all default
     *
     * @return array<array-key, MailingList>
     */
    public function findDefault(): array
    {
        return $this->findBy(
            [
                'defaultSub' => true,
                'onForm' => false,
            ],
            [
                'name' => 'ASC',
            ],
        );
    }
}
