<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Decision model.
 *
 * @ORM\Entity
 */
class Decision
{

    /**
     * Meeting.
     *
     * @ORM\ManyToOne(targetEntity="Meeting", inversedBy="decisions")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="meeting_type", referencedColumnName="type"),
     *  @ORM\JoinColumn(name="meeting_number", referencedColumnName="number"),
     * })
     */
    protected $meeting;

    /**
     * Meeting type.
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $meeting_type;

    /**
     * Meeting number
     *
     * NOTE: This is a hack to make the meeting a primary key here.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $meeting_number;

    /**
     * Point in the meeting in which the decision was made.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $point;

    /**
     * Decision number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $number;

    /**
     * Subdecisions
     *
     * @ORM\OneToMany(targetEntity="SubDecision", mappedBy="decision", cascade={"persist", "remove"})
     */
    protected $subdecisions;

    /**
     * Set the meeting.
     *
     * @param Meeting $meeting
     */
    public function setMeeting(Meeting $meeting)
    {
        $meeting->addDecision($this);
        $this->meeting_type = $meeting->getType();
        $this->meeting_number = $meeting->getNumber();
        $this->meeting = $meeting;
    }

    /**
     * Get the meeting type.
     *
     * @return string
     */
    public function getMeetingType()
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     *
     * @return int
     */
    public function getMeetingNumber()
    {
        return $this->meeting_number;
    }

    /**
     * Get the meeting.
     *
     * @return Meeting
     */
    public function getMeeting()
    {
        return $this->meeting;
    }

    /**
     * Set the point number.
     *
     * @param int $point
     */
    public function setPoint($point)
    {
        $this->point = $point;
    }

    /**
     * Get the point number.
     *
     * @return int
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set the decision number.
     *
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get the decision number.
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Get the subdecisions.
     *
     * @return array
     */
    public function getSubdecisions()
    {
        return $this->subdecisions;
    }

    /**
     * Add a subdecision.
     *
     * @param SubDecision $decision
     */
    public function addSubdecision(SubDecision $subdecision)
    {
        $this->subdecisions[] = $subdecision;
    }

    /**
     * Add multiple subdecisions.
     *
     * @param array $subdecisions
     */
    public function addSubdecisions($subdecisions)
    {
        foreach ($subdecisions as $subdecision) {
            $this->addSubdecision($subdecision);
        }
    }
}
