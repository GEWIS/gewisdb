<?php

declare(strict_types=1);

namespace Database\Model;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use User\Model\User;

use function strip_tags;

/**
 * Abstract audit log entry, can take different types
 */
#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'note' => AuditNote::class,
    ],
)]
abstract class AuditEntry
{
    /**
     * TODO PHP8.3: make this a typed constant so we can change the value later
     */
    protected bool $IMMUTABLE = true;

    /**
     * Entry ID.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected int $id;

    /**
     * When this entry was created
     */
    #[Column(type: 'datetime')]
    protected DateTime $created;

    /**
     * The user who created the entry
     */
    #[OneToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'user',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected User $user;

    /**
     * If this entry is linked to a member, the member who this entry is linked to
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'auditEntries',
    )]
    #[JoinColumn(
        name: 'member',
        referencedColumnName: 'lidnr',
        onDelete: 'cascade',
        nullable: true,
    )]
    protected ?Member $member;

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getUpdated(): DateTime
    {
        return $this->created;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * Get a textual representation of this audit entry
     * The first element is to be the body which after translation can be
     * supplied as an argument to sprintf
     *
     * @return array{bodyPlain: string, bodyFormatted: string, arguments: array<string>}
     */
    final public function getStringPlain(): array
    {
        return [
            'bodyPlain' => $this->getStringBodyPlain(),
            'bodyFormatted' => $this->getStringBodyFormatted(),
            'arguments' => $this->getStringArguments(),
        ];
    }

    /**
     * Whether this entry type can be edited
     * Not implemented
     */
    final public function isEditable(): bool
    {
        return false;
    }

    /**
     * Whether this entry type can be removed
     */
    final public function isDeletable(): bool
    {
        return $this->IMMUTABLE;
    }

    /**
     * Get the string body, currently is constant for all types, but may change
     */
    protected function getStringBodyPlain(): string
    {
        return strip_tags($this->getStringBodyFormatted());
    }

    abstract protected function getStringBodyFormatted(): string;

    /**
     * @return array<string>
     */
    abstract protected function getStringArguments(): array;
}
