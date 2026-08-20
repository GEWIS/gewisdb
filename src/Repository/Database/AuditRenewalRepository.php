<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\AuditRenewal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditRenewal>
 */
class AuditRenewalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            AuditRenewal::class,
        );
    }
}
