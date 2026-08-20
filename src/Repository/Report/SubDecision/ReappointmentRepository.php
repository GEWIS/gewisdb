<?php

declare(strict_types=1);

namespace App\Repository\Report\SubDecision;

use App\Entity\Report\SubDecision\Reappointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reappointment>
 */
class ReappointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Reappointment::class,
        );
    }
}
