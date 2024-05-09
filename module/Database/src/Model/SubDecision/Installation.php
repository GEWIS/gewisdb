<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Database\Model\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Override;

/**
 * Installation into organ.
 */
#[Entity]
#[AssociationOverrides([
    new AssociationOverride(
        name: 'member',
        joinColumns: new JoinColumn(
            name: 'lidnr',
            referencedColumnName: 'lidnr',
            nullable: false,
        ),
        inversedBy: 'installations',
    ),
])]
class Installation extends FoundationReference
{
    /**
     * Function given.
     */
    #[Column(type: 'string')]
    protected string $function;

    /**
     * Reappointment subdecisions if this installation was prolonged (can be done multiple times).
     *
     * @var Collection<array-key, Reappointment>
     */
    #[OneToMany(
        targetEntity: Reappointment::class,
        mappedBy: 'installation',
    )]
    protected Collection $reappointments;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    protected ?Discharge $discharge = null;

    public function __construct()
    {
        $this->reappointments = new ArrayCollection();
    }

    /**
     * Get the function.
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the function.
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @psalm-suppress InvalidNullableReturnType
     */
    #[Override]
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Get the reappointments, if they exist.
     *
     * @return Collection<array-key, Reappointment>
     */
    public function getReappointments(): Collection
    {
        return $this->reappointments;
    }

    /**
     * Get the discharge, if it exists
     */
    public function getDischarge(): ?Discharge
    {
        return $this->discharge;
    }

    protected function getTemplate(): string
    {
        return '%MEMBER% wordt geïnstalleerd als %FUNCTION% van %ORGAN_ABBR%.';
    }

    protected function getAlternativeTemplate(): string
    {
        return '%MEMBER% is installed as %FUNCTION% of %ORGAN_ABBR%.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%MEMBER%' => $this->getMember()->getFullName(),
            '%FUNCTION%' => $this->getFunction(),
            '%ORGAN_ABBR%' => $this->getFoundation()->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%MEMBER%' => $this->getMember()->getFullName(),
            '%FUNCTION%' => $this->getFunction(), // Has no alternative (like the decision hash).
            '%ORGAN_ABBR%' => $this->getFoundation()->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
