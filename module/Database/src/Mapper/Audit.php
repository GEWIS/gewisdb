<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\AuditEntry as AuditEntryModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class Audit
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Persist an audit entry
     */
    public function persist(AuditEntryModel $entry): void
    {
        $entry->assertValid();
        $this->em->persist($entry);
        $this->em->flush();
    }

    /**
     * Remove a member.
     */
    private function remove(AuditEntryModel $entry): void
    {
        $this->em->remove($entry);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     */
    private function getRepository(): EntityRepository
    {
        return $this->em->getRepository(AuditEntryModel::class);
    }
}
