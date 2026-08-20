<?php

declare(strict_types=1);

namespace App\Repository\Database\SubDecision;

use App\Entity\Database\SubDecision\OrganRegulation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganRegulation>
 */
class OrganRegulationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            OrganRegulation::class,
        );
    }
}
