<?php

declare(strict_types=1);

namespace App\Repository\Report\SubDecision;

use App\Entity\Report\SubDecision\Other;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Other>
 */
class OtherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Other::class,
        );
    }
}
