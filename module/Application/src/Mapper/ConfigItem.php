<?php

declare(strict_types=1);

namespace Application\Mapper;

use Application\Model\ConfigItem as ConfigItemModel;
use Application\Model\Enums\ConfigNamespaces;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class ConfigItem
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    public function findByKey(
        ConfigNamespaces $namespace,
        string $key,
    ): ?ConfigItemModel {
        $qb = $this->getRepository()->createQueryBuilder('ci');
        $qb->where('ci.namespace = :namespace')
            ->andWhere('ci.key = :key');

        $qb->setParameter('namespace', $namespace);
        $qb->setParameter('key', $key);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Persist configuration.
     */
    public function persist(ConfigItemModel $item): void
    {
        $this->em->persist($item);
        $this->em->flush();
    }

    /**
     * Remove configuration.
     */
    public function remove(ConfigItemModel $item): void
    {
        $this->em->remove($item);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     */
    private function getRepository(): EntityRepository
    {
        return $this->em->getRepository(ConfigItemModel::class);
    }
}
