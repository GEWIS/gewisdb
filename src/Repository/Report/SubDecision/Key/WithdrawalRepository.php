<?php

declare(strict_types=1);

namespace App\Repository\Report\SubDecision\Key;

use App\Entity\Report\SubDecision\Key\Withdrawal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Withdrawal>
 */
class WithdrawalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Withdrawal::class,
        );
    }
}
