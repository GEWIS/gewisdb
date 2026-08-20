<?php

declare(strict_types=1);

namespace App\Service\Database;

use App\Entity\Database\AuditEntry as AuditEntryModel;
use App\Repository\Database\AuditEntryRepository;

class Audit
{
    public function __construct(private readonly AuditEntryRepository $auditEntryRepository)
    {
    }

    public function persist(AuditEntryModel $entry): void
    {
        $this->auditEntryRepository->persist($entry);
    }
}
