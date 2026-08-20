<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\PaymentLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentLink>
 */
class PaymentLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PaymentLink::class,
        );
    }
}
