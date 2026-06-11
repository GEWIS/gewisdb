<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Mapper\Audit as AuditMapper;
use Database\Model\AuditEntry as AuditEntryModel;

class Audit
{
    public function __construct(
        private readonly AuditMapper $auditMapper,
    ) {
    }

    public function persist(AuditEntryModel $entry): void
    {
        $this->auditMapper->persist($entry);
    }
}
