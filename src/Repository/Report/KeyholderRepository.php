<?php

declare(strict_types=1);

namespace App\Repository\Report;

use App\Entity\Report\Keyholder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Keyholder>
 */
class KeyholderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Keyholder::class,
        );
    }
}
