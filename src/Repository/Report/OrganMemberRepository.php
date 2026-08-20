<?php

declare(strict_types=1);

namespace App\Repository\Report;

use App\Entity\Report\OrganMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganMember>
 */
class OrganMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            OrganMember::class,
        );
    }
}
