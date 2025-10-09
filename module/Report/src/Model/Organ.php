<?php

declare(strict_types=1);

namespace Report\Model;

use Application\Model\Enums\OrganTypes;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Report\Model\SubDecision\Foundation;

/**
 * Organ entity.
 *
 * Note that this entity is derived from the decisions themselves.
 */
#[Entity]
class Organ
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * Abbreviation (only for when organs are created).
     */
    #[Column(type: 'string')]
    private string $abbr;

    /**
     * Name (only for when organs are created).
     */
    #[Column(type: 'string')]
    private string $name;

    /**
     * Type of the organ.
     */
    #[Column(
        type: 'string',
        enumType: OrganTypes::class,
    )]
    private OrganTypes $type;

    /**
     * Reference to foundation of organ.
     */
    #[OneToOne(
        inversedBy: 'organ',
        targetEntity: Foundation::class,
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'r_decision_point',
        referencedColumnName: 'decision_point',
    )]
    #[JoinColumn(
        name: 'r_decision_number',
        referencedColumnName: 'decision_number',
    )]
    #[JoinColumn(
        name: 'r_sequence',
        referencedColumnName: 'sequence',
    )]
    private Foundation $foundation;

    /**
     * Foundation date.
     */
    #[Column(type: 'date')]
    private DateTime $foundationDate;

    /**
     * Abrogation date.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    private ?DateTime $abrogationDate = null;

    /**
     * Reference to members.
     *
     * @var Collection<array-key, OrganMember>
     */
    #[OneToMany(
        mappedBy: 'organ',
        targetEntity: OrganMember::class,
    )]
    private Collection $members;

    /**
     * Reference to subdecisions.
     *
     * @var Collection<array-key, SubDecision>
     */
    #[ManyToMany(
        targetEntity: SubDecision::class,
        cascade: ['remove', 'persist'],
    )]
    #[JoinTable(name: 'organs_subdecisions')]
    #[JoinColumn(
        name: 'organ_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    #[InverseJoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'meeting_type',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    #[InverseJoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'meeting_number',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    #[InverseJoinColumn(
        name: 'decision_point',
        referencedColumnName: 'decision_point',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    #[InverseJoinColumn(
        name: 'decision_number',
        referencedColumnName: 'decision_number',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    #[InverseJoinColumn(
        name: 'subdecision_sequence',
        referencedColumnName: 'sequence',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private Collection $subdecisions;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->subdecisions = new ArrayCollection();
    }

    /**
     * Get the ID.
     *
     * @psalm-ignore-nullable-return
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the ID.
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the abbreviation.
     */
    public function getAbbr(): string
    {
        return $this->abbr;
    }

    /**
     * Set the abbreviation.
     */
    public function setAbbr(string $abbr): void
    {
        $this->abbr = $abbr;
    }

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the type.
     */
    public function getType(): OrganTypes
    {
        return $this->type;
    }

    /**
     * Set the type.
     */
    public function setType(OrganTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the foundation.
     */
    public function getFoundation(): Foundation
    {
        return $this->foundation;
    }

    /**
     * Set the foundation.
     */
    public function setFoundation(Foundation $foundation): void
    {
        $this->foundation = $foundation;
    }

    /**
     * Get the foundation date.
     */
    public function getFoundationDate(): DateTime
    {
        return $this->foundationDate;
    }

    /**
     * Set the foundation date.
     */
    public function setFoundationDate(DateTime $foundationDate): void
    {
        $this->foundationDate = $foundationDate;
    }

    /**
     * Get the abrogation date.
     */
    public function getAbrogationDate(): ?DateTime
    {
        return $this->abrogationDate;
    }

    /**
     * Set the abrogation date.
     */
    public function setAbrogationDate(?DateTime $abrogationDate): void
    {
        $this->abrogationDate = $abrogationDate;
    }

    /**
     * Get the members.
     *
     * @return Collection<array-key, OrganMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    /**
     * Add multiple subdecisions.
     *
     * @param SubDecision[] $subdecisions
     */
    public function addSubdecisions(array $subdecisions): void
    {
        foreach ($subdecisions as $subdecision) {
            $this->addSubdecision($subdecision);
        }
    }

    /**
     * Add a subdecision.
     */
    public function addSubdecision(SubDecision $subdecision): void
    {
        if ($this->subdecisions->contains($subdecision)) {
            return;
        }

        $this->subdecisions[] = $subdecision;
    }
}
