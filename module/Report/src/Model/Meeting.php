<?php

declare(strict_types=1);

namespace Report\Model;

use Application\Model\Enums\MeetingTypes;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Report\Model\SubDecision\Minutes;

/**
 * Meeting model.
 */
#[Entity]
class Meeting
{
    /**
     * Meeting type.
     */
    #[Id]
    #[Column(
        type: 'string',
        enumType: MeetingTypes::class,
    )]
    private MeetingTypes $type;

    /**
     * Meeting number.
     */
    #[Id]
    #[Column(type: 'integer')]
    private int $number;

    /**
     * Meeting date.
     */
    #[Column(type: 'date')]
    private DateTime $date;

    /**
     * Decisions.
     *
     * @var Collection<array-key, Decision>
     */
    #[OneToMany(
        targetEntity: Decision::class,
        mappedBy: 'meeting',
    )]
    private Collection $decisions;

    #[OneToOne(
        targetEntity: Minutes::class,
        mappedBy: 'meeting',
    )]
    private Minutes $minutes;

    public function __construct()
    {
        $this->decisions = new ArrayCollection();
    }

    /**
     * Get the meeting type.
     */
    public function getType(): MeetingTypes
    {
        return $this->type;
    }

    /**
     * Get the meeting number.
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Set the meeting type.
     */
    public function setType(MeetingTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Set the meeting number.
     */
    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * Get the meeting date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the meeting date.
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get the decisions.
     *
     * @return Collection<array-key, Decision>
     */
    public function getDecisions(): Collection
    {
        return $this->decisions;
    }

    /**
     * Add a decision.
     */
    public function addDecision(Decision $decision): void
    {
        $this->decisions[] = $decision;
    }

    /**
     * Add multiple decisions.
     *
     * @param Decision[] $decisions
     */
    public function addDecisions(array $decisions): void
    {
        foreach ($decisions as $decision) {
            $this->addDecision($decision);
        }
    }
}
