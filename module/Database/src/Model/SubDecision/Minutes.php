<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Application\Model\Enums\MeetingTypes;
use Database\Model\Meeting;
use Database\Model\SubDecision;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

use function strval;

/**
 * Decisions on minutes.
 */
#[Entity]
class Minutes extends SubDecision
{
    /**
     * Reference to the meeting.
     */
    #[OneToOne(
        targetEntity: Meeting::class,
        inversedBy: 'minutes',
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'number',
    )]
    protected Meeting $meeting;

    /**
     * If the minutes were approved.
     */
    #[Column(type: 'boolean')]
    protected bool $approval;

    /**
     * If there were changes made.
     */
    #[Column(type: 'boolean')]
    protected bool $changes;

    /**
     * Get the target.
     */
    public function getTarget(): Meeting
    {
        return $this->meeting;
    }

    /**
     * Set the target.
     */
    public function setTarget(Meeting $meeting): void
    {
        $this->meeting = $meeting;
    }

    /**
     * Get approval status.
     */
    public function getApproval(): bool
    {
        return $this->approval;
    }

    /**
     * Set approval status.
     */
    public function setApproval(bool $approval): void
    {
        $this->approval = $approval;
    }

    /**
     * Get if changes were made.
     */
    public function getChanges(): bool
    {
        return $this->changes;
    }

    /**
     * Set if changes were made.
     */
    public function setChanges(bool $changes): void
    {
        $this->changes = $changes;
    }

    protected function getTemplate(): string
    {
        return 'De notulen van de %NUMBER%e %TYPE%%AUTHOR% worden%APPROVAL%%THANK%%CHANGES%.';
    }

    protected function getAlternativeTemplate(): string
    {
        return 'The minutes of the %NUMBER%th %TYPE%%AUTHOR% are%APPROVAL%%THANK%%CHANGES%.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%TYPE%' => $this->getTarget()->getType()->value,
            '%NUMBER%' => strval($this->getTarget()->getNumber()),
            '%APPROVAL%' => $this->getApproval() ? ' goedgekeurd' : ' afgekeurd',
            '%AUTHOR%' => MeetingTypes::BV === $this->getTarget()->getType() ? ''
                : ' door ' . $this->getMember()->getFullName(),
            '%CHANGES%' => $this->getApproval() && $this->getChanges() ? ' met genoemde wijzigingen' : '',
            '%THANK%' => MeetingTypes::BV === $this->getTarget()->getType() ? ' met dank aan de notulist' : '',
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%TYPE%' => $this->getTarget()->getType()->value,
            '%NUMBER%' => strval($this->getTarget()->getNumber()),
            '%APPROVAL%' => $this->getApproval() ? ' approved' : ' disapproved',
            '%AUTHOR%' => MeetingTypes::BV === $this->getTarget()->getType() ? ''
                : ' by ' . $this->getMember()->getFullName(),
            '%CHANGES%' => $this->getApproval() && $this->getChanges() ? ' with mentioned changes' : '',
            '%THANK%' => MeetingTypes::BV === $this->getTarget()->getType() ? ' thanks to the minute taker' : '',
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
