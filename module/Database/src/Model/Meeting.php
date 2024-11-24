<?php

declare(strict_types=1);

namespace Database\Model;

use Application\Model\Enums\MeetingTypes;
use Database\Model\SubDecision\Minutes;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use ValueError;

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
    protected MeetingTypes $type;

    /**
     * Meeting number.
     *
     * See the getNumber and setNumber implementations to maintain the >=0 assumption
     */
    #[Id]
    #[Column(
        type: 'integer',
        options: [
            'unsigned' => true,
        ],
    )]
    protected int $number;

    /**
     * Meeting date.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * Decisions.
     *
     * @var Collection<array-key, Decision>
     */
    #[OneToMany(
        targetEntity: Decision::class,
        mappedBy: 'meeting',
        cascade: ['persist'],
    )]
    protected Collection $decisions;

    #[OneToOne(
        targetEntity: Minutes::class,
        mappedBy: 'meeting',
    )]
    protected Minutes $minutes;

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
     * Set the meeting type.
     */
    public function setType(MeetingTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the meeting number.
     *
     * In practice, unsigned is not possible in PostgreSQL:
     * https://www.doctrine-project.org/projects/doctrine-dbal/en/stable/reference/types.html#mapping-matrix
     *
     * Hence, we raise an error if the number is negative
     *
     * @return non-negative-int
     */
    public function getNumber(): int
    {
        if ($this->number < 0) {
            throw new ValueError('Meeting ID < 0');
        }

        return $this->number;
    }

    /**
     * Set the meeting number.
     *
     * @param non-negative-int $number
     */
    public function setNumber(int $number): void
    {
        if ($number < 0) {
            throw new ValueError('Cannot set meeting ID < 0');
        }

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
        $this->decisions->add($decision);
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

    /**
     * Transform into an array.
     *
     * @return array{
     *     meeting_type: MeetingTypes,
     *     meeting_number: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'meeting_type' => $this->getType(),
            'meeting_number' => $this->getNumber(),
        ];
    }

    /**
     * Return the meeting number as short ordinal, e.g. "1st" or "3e"
     *
     * Verify the logic here: https://3v4l.org/fYUoo
     */
    public function getNumberAsOrdinal(?string $locale): string
    {
        if (null === $locale) {
            $locale = 'nl_NL';
        }

        if ('en_GB' === $locale) {
            $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
            if (($this->getNumber() % 100 >= 11) && ($this->getNumber() % 100 <= 13)) {
                return $this->getNumber() . 'th';
            }

            return $this->getNumber() . $ends[$this->getNumber() % 10];
        }

        return $this->getNumber() . 'e';
    }
}
