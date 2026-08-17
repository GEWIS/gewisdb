<?php

declare(strict_types=1);

namespace App\ViewModel\Checker\Error;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\SubDecision\Foundation as FoundationModel;
use App\ViewModel\Checker\Error;
use Override;

use function sprintf;

/**
 * Error for when an organ is founded during a meeting that it cannot be founded in.
 *
 * ALV-commissies can only be created during ALV's
 * All other organs can only be created during BV's and Virt's
 *
 * @extends Error<FoundationModel>
 */
class OrganMeetingType extends Error
{
    public function __construct(FoundationModel $foundation)
    {
        parent::__construct(
            $foundation->getDecision()->getMeeting(),
            $foundation,
        );
    }

    /**
     * Get the organ that was founded.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision();
    }

    /**
     * Get the type of organ that was founded.
     */
    public function getOrganType(): OrganTypes
    {
        return $this->getSubDecision()->getOrganType();
    }

    /**
     * Get the type of meeting in which the organ was founded.
     */
    public function getMeetingType(): MeetingTypes
    {
        return $this->getSubDecision()->getDecision()->getMeeting()->getType();
    }

    #[Override]
    public function asText(): string
    {
        return sprintf(
            'Organ %s of type %s cannot be founded during a meeting of type %s.',
            $this->getOrgan()->getName(),
            $this->getOrganType()->value,
            $this->getMeetingType()->value,
        );
    }
}
