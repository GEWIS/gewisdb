<?php

declare(strict_types=1);

namespace App\Entity\Database;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\SubDecision\Minutes;
use App\Repository\Database\MeetingRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;
use NumberFormatter;
use ValueError;

/**
 * Meeting model.
 */
#[Entity(repositoryClass: MeetingRepository::class)]
class Meeting
{
    /**
     * Meeting type.
     */
    #[Id]
    // Length spelled out: ORM 3 only copies an explicit length onto the join columns that reference this one,
    // which would otherwise become unbounded VARCHAR.
    #[Column(
        length: 255,
        enumType: MeetingTypes::class,
    )]
    private MeetingTypes $type;

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
    private int $number;

    /**
     * Meeting date.
     */
    #[Column(type: 'date')]
    private DateTime $date;

    /**
     * Decisions.
     *
     * A meeting works through its agenda from top to bottom, and a decision may build on one taken earlier in the same
     * meeting. Anything replaying the meeting has to see them in that order, so it is fixed here instead of being left
     * to whatever order the database happens to return the rows in.
     *
     * @var Collection<array-key, Decision>
     */
    #[OneToMany(
        targetEntity: Decision::class,
        mappedBy: 'meeting',
        cascade: ['persist'],
    )]
    #[OrderBy(value: [
        'point' => 'ASC',
        'number' => 'ASC',
    ])]
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
     * Remove a decision that is not going to be recorded after all.
     *
     * {@see Decision::setMeeting()} adds a decision here as soon as it is built, and this collection cascades
     * persists, so a decision that is turned down has to be taken back out or the next flush would still write it.
     */
    public function removeDecision(Decision $decision): void
    {
        $this->decisions->removeElement($decision);
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
            $locale = AppLanguages::Dutch->getLocale();
        }

        if (AppLanguages::English->getLocale() === $locale) {
            return new NumberFormatter($locale, NumberFormatter::ORDINAL)->format($this->getNumber());
        }

        return $this->getNumber() . 'e';
    }
}
