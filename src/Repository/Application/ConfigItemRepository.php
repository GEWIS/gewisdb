<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\ConfigItem;
use App\Entity\Application\Enums\ConfigNamespaces;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigItem>
 */
class ConfigItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ConfigItem::class,
        );
    }

    public function findByKey(
        ConfigNamespaces $namespace,
        string $key,
    ): ?ConfigItem {
        $qb = $this->createQueryBuilder('ci');
        $qb->where('ci.namespace = :namespace')
            ->andWhere('ci.key = :key');

        $qb->setParameter(
            'namespace',
            $namespace,
        );
        $qb->setParameter(
            'key',
            $key,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Persist configuration.
     */
    public function persist(ConfigItem $item): void
    {
        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove configuration.
     */
    public function remove(ConfigItem $item): void
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
    }
}
