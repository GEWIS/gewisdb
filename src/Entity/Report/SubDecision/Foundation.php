<?php

declare(strict_types=1);

namespace App\Entity\Report\SubDecision;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Report\Organ;
use App\Entity\Report\SubDecision;
use App\Repository\Report\SubDecision\FoundationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Foundation of an organ.
 */
#[Entity(repositoryClass: FoundationRepository::class)]
class Foundation extends SubDecision
{
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
     * Purpose (only for when organs are created).
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    private ?string $purpose = null;

    /**
     * Type of the organ.
     */
    #[Column(
        enumType: OrganTypes::class,
    )]
    private OrganTypes $organType;

    /**
     * References from other subdecisions to this organ.
     *
     * @var Collection<array-key, FoundationReference>
     */
    #[OneToMany(
        targetEntity: FoundationReference::class,
        mappedBy: 'foundation',
    )]
    private Collection $references;

    /**
     * Organ entry for this organ.
     */
    #[OneToOne(
        targetEntity: Organ::class,
        mappedBy: 'foundation',
    )]
    private Organ $organ;

    public function __construct()
    {
        $this->references = new ArrayCollection();
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
     * Get the purpose.
     */
    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    /**
     * Set the purpose.
     */
    public function setPurpose(?string $purpose): void
    {
        $this->purpose = $purpose;
    }

    /**
     * Get the type.
     */
    public function getOrganType(): OrganTypes
    {
        return $this->organType;
    }

    /**
     * Set the type.
     */
    public function setOrganType(OrganTypes $organType): void
    {
        $this->organType = $organType;
    }

    /**
     * Get the references.
     *
     * @return Collection<array-key, FoundationReference>
     */
    public function getReferences(): Collection
    {
        return $this->references;
    }

    /**
     * Set the referenced organ.
     *
     * Kept in step with the owning side, so that an organ only just derived from this foundation can be found right
     * away, without having to go through the database for it.
     */
    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * Forget what was derived from this subdecision, because it no longer exists.
     *
     * Leaves the property uninitialised again, which is how the rest of the code recognises that there is nothing.
     */
    public function clearOrgan(): void
    {
        unset($this->organ);
    }

    /**
     * Get the referenced organ.
     */
    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    /**
     * Get an array with all information.
     *
     * Mostly usefull for usage with JSON.
     *
     * @return array{
     *     meeting_type: MeetingTypes,
     *     meeting_number: int,
     *     decision_point: int,
     *     decision_number: int,
     *     subdecision_sequence: int,
     *     abbr: string,
     *     name: string,
     *     organtype: OrganTypes,
     * }
     */
    public function toArray(): array
    {
        $decision = $this->getDecision();

        return [
            'meeting_type' => $decision->getMeeting()->getType(),
            'meeting_number' => $decision->getMeeting()->getNumber(),
            'decision_point' => $decision->getPoint(),
            'decision_number' => $decision->getNumber(),
            'subdecision_sequence' => $this->getSequence(),
            'abbr' => $this->getAbbr(),
            'name' => $this->getName(),
            'organtype' => $this->getOrganType(),
        ];
    }
}
