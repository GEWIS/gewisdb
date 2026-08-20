<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\AuditNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditNote>
 */
class AuditNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            AuditNote::class,
        );
    }
}
