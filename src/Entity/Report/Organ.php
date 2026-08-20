<?php

declare(strict_types=1);

namespace App\Entity\Report;

use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Report\SubDecision\Foundation;
use App\Repository\Report\OrganRepository;
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
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Organ entity.
 *
 * Note that this entity is derived from the decisions themselves.
 *
 * ORM 2 emitted a `<field>_uniq` unique index for the join columns of a one-to-one owning side; ORM 3 emits a plain
 * foreign-key index instead. Declared here so the relation stays one-to-one in the database, under the name the
 * existing schema already uses.
 */
#[UniqueConstraint(
    name: 'foundation_uniq',
    columns: [
        'r_meeting_type',
        'r_meeting_number',
        'r_decision_point',
        'r_decision_number',
        'r_sequence',
    ],
)]
#[Entity(repositoryClass: OrganRepository::class)]
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
     * Membership of a body is derived from the decisions about that body, so it cannot outlast the body itself: a
     * body whose foundation was annulled never existed, and neither did anyone's membership of it. Deleting the body
     * therefore deletes the memberships derived from it, which the database is deliberately not told to do — what
     * goes and what stays is the projection's to decide rather than a cascade's to settle behind its back.
     *
     * @var Collection<array-key, OrganMember>
     */
    #[OneToMany(
        mappedBy: 'organ',
        targetEntity: OrganMember::class,
        cascade: ['remove'],
    )]
    private Collection $members;

    /**
     * Reference to subdecisions.
     *
     * @var Collection<array-key, SubDecision>
     */
    #[ManyToMany(
        targetEntity: SubDecision::class,
        cascade: ['persist'],
    )]
    #[JoinTable(name: 'organs_subdecisions')]
    #[JoinColumn(
        name: 'organ_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'meeting_type',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'meeting_number',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'decision_point',
        referencedColumnName: 'decision_point',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'decision_number',
        referencedColumnName: 'decision_number',
        nullable: false,
    )]
    #[InverseJoinColumn(
        name: 'subdecision_sequence',
        referencedColumnName: 'sequence',
        nullable: false,
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
     * Add a member.
     *
     * Kept in step with the owning side, so that a member installed earlier in the same meeting is already part of the
     * organ when a later decision in that meeting asks who is in it.
     */
    public function addMember(OrganMember $member): void
    {
        if ($this->members->contains($member)) {
            return;
        }

        $this->members[] = $member;
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

    /**
     * Remove a subdecision, if it is related to this organ.
     */
    public function removeSubdecision(SubDecision $subdecision): void
    {
        if (!$this->subdecisions->contains($subdecision)) {
            return;
        }

        $this->subdecisions->removeElement($subdecision);
    }
}
