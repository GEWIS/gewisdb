<?php

declare(strict_types=1);

namespace Database\Model;

use Application\Model\Enums\MeetingTypes;
use Database\Model\SubDecision\Destroy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;

use function implode;

/**
 * Decision model.
 */
#[Entity]
class Decision
{
    /**
     * Meeting.
     */
    #[ManyToOne(
        targetEntity: Meeting::class,
        inversedBy: 'decisions',
    )]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'type',
        nullable: false,
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'number',
        nullable: false,
    )]
    protected Meeting $meeting;

    /**
     * Meeting type.
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     */
    #[Id]
    #[Column(
        type: 'string',
        enumType: MeetingTypes::class,
    )]
    protected MeetingTypes $meeting_type;

    /**
     * Meeting number.
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $meeting_number;

    /**
     * Point in the meeting in which the decision was made.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $point;

    /**
     * Decision number.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $number;

    /**
     * Subdecisions.
     *
     * @var Collection<array-key, SubDecision> $subdecisions
     */
    #[OneToMany(
        targetEntity: SubDecision::class,
        mappedBy: 'decision',
        cascade: ['persist', 'remove'],
    )]
    #[OrderBy(value: ['number' => 'ASC'])]
    protected Collection $subdecisions;

    /**
     * Destroyed by.
     */
    #[OneToOne(
        targetEntity: Destroy::class,
        mappedBy: 'target',
    )]
    protected ?Destroy $destroyedby = null;

    /**
     * Set the meeting.
     */
    public function setMeeting(Meeting $meeting): void
    {
        $this->subdecisions = new ArrayCollection();

        $meeting->addDecision($this);
        $this->meeting_type = $meeting->getType();
        $this->meeting_number = $meeting->getNumber();
        $this->meeting = $meeting;
    }

    /**
     * Get the meeting type.
     */
    public function getMeetingType(): MeetingTypes
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     */
    public function getMeetingNumber(): int
    {
        return $this->meeting_number;
    }

    /**
     * Get the meeting.
     */
    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    /**
     * Set the point number.
     */
    public function setPoint(int $point): void
    {
        $this->point = $point;
    }

    /**
     * Get the point number.
     */
    public function getPoint(): int
    {
        return $this->point;
    }

    /**
     * Set the decision number.
     */
    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * Get the decision number.
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Get the subdecisions.
     *
     * @return Collection<array-key, SubDecision>
     */
    public function getSubdecisions(): Collection
    {
        return $this->subdecisions;
    }

    /**
     * Add a subdecision.
     */
    public function addSubdecision(SubDecision $subdecision): void
    {
        $this->subdecisions[] = $subdecision;
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
     * Get the subdecision by which this decision is destroyed.
     *
     * Or null, if it wasn't destroyed.
     */
    public function getDestroyedBy(): ?Destroy
    {
        return $this->destroyedby;
    }

    /**
     * Check if this decision is destroyed by another decision.
     */
    public function isDestroyed(): bool
    {
        return null !== $this->destroyedby;
    }

    public function getContent(): string
    {
        $content = [];
        foreach ($this->getSubdecisions() as $subdecision) {
            $content[] = $subdecision->getContent();
        }

        $content = implode(' ', $content);

        return $content;
    }

    /**
     * Transform into an array.
     *
     * @return array{
     *     meeting_type: MeetingTypes,
     *     meeting_number: int,
     *     decision_point: int,
     *     decision_number: int,
     *     content: string,
     * }
     */
    public function toArray(): array
    {
        $content = $this->getContent();

        return [
            'meeting_type' => $this->getMeeting()->getType(),
            'meeting_number' => $this->getMeeting()->getNumber(),
            'decision_point' => $this->getPoint(),
            'decision_number' => $this->getNumber(),
            'content' => $content,
        ];
    }
}
