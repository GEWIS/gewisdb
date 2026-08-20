<?php

declare(strict_types=1);

namespace App\Repository\Report\SubDecision\Key;

use App\Entity\Report\SubDecision\Key\Granting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Granting>
 */
class GrantingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Granting::class,
        );
    }
}
