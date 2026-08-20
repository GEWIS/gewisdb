<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\RenewalLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RenewalLink>
 */
class RenewalLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            RenewalLink::class,
        );
    }
}
