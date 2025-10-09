<?php

declare(strict_types=1);

namespace Database\Model;

use DateTime;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * Class for registering renewals by the member or another user
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
class AuditRenewal extends AuditEntry
{
    /** @psalm-suppress InvalidClassConstantType */
    private bool $IMMUTABLE = true;

    /**
     * Expiration value before this renewal took place
     */
    #[Column(type: 'datetime')]
    private DateTime $oldExpiration;

    /**
     * Expiration value after the renewal
     */
    #[Column(type: 'datetime')]
    private DateTime $newExpiration;

    final public static function fromRenewalLink(RenewalLink $renewalLink): AuditRenewal
    {
        $auditRenewal = new AuditRenewal();
        $auditRenewal->setOldExpiration($renewalLink->getCurrentExpiration());
        $auditRenewal->setNewExpiration($renewalLink->getNewExpiration());
        $auditRenewal->setMember($renewalLink->getMember());

        return $auditRenewal;
    }

    /**
     * Get the old expiration date.
     */
    public function getOldExpiration(): DateTime
    {
        return $this->oldExpiration;
    }

    /**
     * Set the old expiration date.
     */
    public function setOldExpiration(DateTime $oldExpiration): void
    {
        $this->oldExpiration = $oldExpiration;
    }

    /**
     * Get the new expiration date.
     */
    public function getNewExpiration(): DateTime
    {
        return $this->newExpiration;
    }

    /**
     * Set the new expiration date.
     */
    public function setNewExpiration(DateTime $newExpiration): void
    {
        $this->newExpiration = $newExpiration;
    }

    /**
     * Check if the renewal was done by the member
     */
    private function isSelfRenewal(): bool
    {
        return null === $this->user;
    }

    private function getStringRenewalType(): string
    {
        return $this->isSelfRenewal() ? 'Self-renewal' : 'Renewal';
    }

    /**
     * Get a textual representation of this audit entry
     */
    protected function getStringBodyFormatted(): string
    {
        return '<strong>%s</strong> of <emph>%s</emph> until <br/>%s';
    }

    /**
     * @return array<?string>
     */
    protected function getStringArguments(): array
    {
        return [
            $this->getStringRenewalType(),
            $this->getMember()->getFullName(),
            $this->getNewExpiration()->format('l j F Y'),
        ];
    }
}
