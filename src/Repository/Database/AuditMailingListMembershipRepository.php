<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\AuditMailingListMembership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditMailingListMembership>
 */
class AuditMailingListMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            AuditMailingListMembership::class,
        );
    }
}
