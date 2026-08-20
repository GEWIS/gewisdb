<?php

declare(strict_types=1);

namespace App\Entity\Database;

use App\Repository\Database\AuditNoteRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Override;

/**
 * Class for registering manual notes
 */
#[Entity(repositoryClass: AuditNoteRepository::class)]
class AuditNote extends AuditEntry
{
    protected const bool IMMUTABLE = false;

    /**
     * The note itself
     */
    #[Column(type: 'string')]
    private string $note;

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    /**
     * Get a textual representation of this audit entry
     */
    #[Override]
    protected function getStringBodyFormatted(): string
    {
        return '<strong>Note</strong> on <emph>%s</emph>: <br/>%s';
    }

    /**
     * @return array<string>
     */
    #[Override]
    protected function getStringArguments(): array
    {
        return [
            $this->getMember()->getFullName(),
            $this->getNote(),
        ];
    }
}
