<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\ActionLink as ActionLinkModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class ActionLink
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Find an action link by token
     */
    public function findByToken(string $token): ?ActionLinkModel
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('al')
            ->from(ActionLinkModel::class, 'al')
            ->where('al.token = :token');

        $qb->setParameter(':token', $token);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(ActionLinkModel::class);
    }
}
