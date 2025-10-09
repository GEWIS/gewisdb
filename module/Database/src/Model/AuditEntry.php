<?php

declare(strict_types=1);

namespace Database\Model;

use Database\Model\Trait\CreatedTrait;
use Database\Model\Trait\UpdatedTrait;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use LogicException;
use UnexpectedValueException;
use User\Model\User;

use function strip_tags;

/**
 * Abstract audit log entry, can take different types
 */
#[Entity]
#[HasLifecycleCallbacks]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'note' => AuditNote::class,
        'renewal' => AuditRenewal::class,
    ],
)]
abstract class AuditEntry
{
    use CreatedTrait;
    use UpdatedTrait;

    /**
     * TODO PHP8.3: make this a typed constant so we can change the value later
     */
    private bool $IMMUTABLE = true;

    /**
     * Entry ID.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * The user who created the entry
     */
    #[OneToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'user_id',
        referencedColumnName: 'id',
        onDelete: 'set null',
        nullable: true,
    )]
    protected ?User $user = null;

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
    private ?Member $member = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUserName(): string
    {
        if (null === $this->user) {
            return '';
        }

        return $this->user->getName();
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function setMember(Member $member): void
    {
        if (null !== $this->member && $this->member !== $member) {
            throw new LogicException('Must not link an audit entry to another object after creation');
        }

        $this->member = $member;
    }

    /**
     * It is not possible to require a link always when constructing the model, but we want
     * to always link at least one of the linkable objects. Currently only member is possible
     */
    public function assertValid(): void
    {
        if (null === $this->member) {
            throw new UnexpectedValueException(
                'Asserting that object of type ' .
                $this::class .
                ' is linked to at least one object.',
            );
        }
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
    private function getStringBodyPlain(): string
    {
        return strip_tags($this->getStringBodyFormatted());
    }

    abstract protected function getStringBodyFormatted(): string;

    /**
     * @return array<string>
     */
    abstract protected function getStringArguments(): array;
}
