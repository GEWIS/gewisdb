<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Application\Model\Enums\MeetingTypes;
use Application\Model\Enums\OrganTypes;
use Database\Model\SubDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * Foundation of an organ.
 */
#[Entity]
class Foundation extends SubDecision
{
    /**
     * Abbreviation (only for when organs are created)
     */
    #[Column(type: 'string')]
    protected string $abbr;

    /**
     * Name (only for when organs are created)
     */
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Type of the organ.
     */
    #[Column(
        type: 'string',
        enumType: OrganTypes::class,
    )]
    protected OrganTypes $organType;

    /**
     * References from other subdecisions to this organ.
     *
     * @var Collection<array-key, FoundationReference>
     */
    #[OneToMany(
        targetEntity: FoundationReference::class,
        mappedBy: 'foundation',
    )]
    protected Collection $references;

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

    protected function getTemplate(): string
    {
        return '%ORGAN_TYPE% %ORGAN_NAME% met afkorting %ORGAN_ABBR% wordt opgericht.';
    }

    protected function getAlternativeTemplate(): string
    {
        return '%ORGAN_TYPE% %ORGAN_NAME% with abbreviation %ORGAN_ABBR% is established.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%ORGAN_TYPE%' => $this->getOrganType()->getName(),
            '%ORGAN_NAME%' => $this->getName(),
            '%ORGAN_ABBR%' => $this->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%ORGAN_TYPE%' => $this->getOrganType()->getAlternativeName(),
            '%ORGAN_NAME%' => $this->getName(),
            '%ORGAN_ABBR%' => $this->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }

    /**
     * Get an array with all information.
     *
     * Mostly useful for usage with JSON.
     *
     * @return array{
     *     meeting_type: MeetingTypes,
     *     meeting_number: int,
     *     decision_point: int,
     *     decision_number: int,
     *     subdecision_number: int,
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
            'subdecision_number' => $this->getNumber(),
            'abbr' => $this->getAbbr(),
            'name' => $this->getName(),
            'organtype' => $this->getOrganType(),
        ];
    }
}
