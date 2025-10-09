<?php

declare(strict_types=1);

namespace Database\Model;

use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * Class for registering manual notes
 */
#[Entity]
#[AssociationOverrides([
    new AssociationOverride(
        name: 'member',
        joinColumns: new JoinColumn(
            name: 'member',
            referencedColumnName: 'lidnr',
            onDelete: 'cascade',
            nullable: false,
        ),
    ),
])]
class AuditNote extends AuditEntry
{
    /** @psalm-suppress InvalidClassConstantType */
    private bool $IMMUTABLE = false;

    /**
     * The note itself
     */
    #[Column(type: 'string')]
    private ?string $note = null;

    public function getNote(): ?string
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
    protected function getStringBodyFormatted(): string
    {
        return '<strong>Note</strong> on <emph>%s</emph>: <br/>%s';
    }

    /**
     * @return array<?string>
     */
    protected function getStringArguments(): array
    {
        return [
            $this->getMember()->getFullName(),
            $this->getNote(),
        ];
    }
}
