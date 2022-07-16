<?php

namespace Database\Model;

use Application\Model\Enums\MeetingTypes;
use Database\Model\SubDecision\Destroy;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    JoinColumn,
    ManyToOne,
    OneToMany,
    OneToOne,
    OrderBy,
};

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
        inversedBy: "decisions",
    )]
    #[JoinColumn(
        name: "meeting_type",
        referencedColumnName: "type",
        nullable: false,
    )]
    #[JoinColumn(
        name: "meeting_number",
        referencedColumnName: "number",
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
        type: "string",
        enumType: MeetingTypes::class,
    )]
    protected MeetingTypes $meeting_type;

    /**
     * Meeting number.
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $meeting_number;

    /**
     * Point in the meeting in which the decision was made.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $point;

    /**
     * Decision number.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $number;

    /**
     * Subdecisions.
     */
    #[OneToMany(
        targetEntity: SubDecision::class,
        mappedBy: "decision",
        cascade: ["persist", "remove"],
    )]
    #[OrderBy(value: ["number" => "ASC"])]
    protected Collection $subdecisions;

    /**
     * Destroyed by.
     */
    #[OneToOne(
        targetEntity: Destroy::class,
        mappedBy: "target",
    )]
    protected ?Destroy $destroyedby = null;

    /**
     * Set the meeting.
     *
     * @param Meeting $meeting
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
     *
     * @return MeetingTypes
     */
    public function getMeetingType(): MeetingTypes
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     *
     * @return int
     */
    public function getMeetingNumber(): int
    {
        return $this->meeting_number;
    }

    /**
     * Get the meeting.
     *
     * @return Meeting
     */
    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    /**
     * Set the point number.
     *
     * @param int $point
     */
    public function setPoint(int $point): void
    {
        $this->point = $point;
    }

    /**
     * Get the point number.
     *
     * @return int
     */
    public function getPoint(): int
    {
        return $this->point;
    }

    /**
     * Set the decision number.
     *
     * @param int $number
     */
    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * Get the decision number.
     *
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Get the subdecisions.
     *
     * @return Collection
     */
    public function getSubdecisions(): Collection
    {
        return $this->subdecisions;
    }

    /**
     * Add a subdecision.
     *
     * @param SubDecision $subdecision
     */
    public function addSubdecision(SubDecision $subdecision): void
    {
        $this->subdecisions[] = $subdecision;
    }

    /**
     * Add multiple subdecisions.
     *
     * @param array $subdecisions
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
     *
     * @return SubDecision\Destroy
     */
    public function getDestroyedBy(): SubDecision\Destroy
    {
        return $this->destroyedby;
    }

    /**
     * Check if this decision is destroyed by another decision.
     *
     * @return bool
     */
    public function isDestroyed(): bool
    {
        return null !== $this->destroyedby;
    }

    /**
     * Transform into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $content = [];
        foreach ($this->getSubdecisions() as $subdecision) {
            $content[] = $subdecision->getContent();
        }

        $content = implode(' ', $content);

        return [
            'meeting_type' => $this->getMeeting()->getType(),
            'meeting_number' => $this->getMeeting()->getNumber(),
            'decision_point' => $this->getPoint(),
            'decision_number' => $this->getNumber(),
            'content' => $content,
        ];
    }
}
