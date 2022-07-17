<?php

namespace Checker\Model\Error;

use Application\Model\Enums\MeetingTypes;
use Application\Model\Enums\OrganTypes;
use Checker\Model\Error;
use Database\Model\SubDecision\Foundation as FoundationModel;

/**
 * Class OrganMeetingType
 *
 * This class denotes an error where an organ is created during a meeting type that it was not allowed to be created in
 *
 * AV-commissies can only be created during AV's
 * All other organs can only be created during BV's and Virt's
 *
 * @package Checker\Model\Error
 */
class OrganMeetingType extends Error
{
    public function __construct(FoundationModel $foundation)
    {
        parent::__construct($foundation->getDecision()->getMeeting(), $foundation);
    }

    /**
     * @return OrganTypes Type of organ that was created
     */
    public function getOrganType(): OrganTypes
    {
        return $this->getSubDecision()->getOrganType();
    }

    /**
     * @return MeetingTypes Type of meeting that this organ was created
     */
    public function getMeetingType(): MeetingTypes
    {
        return $this->getSubDecision()->getDecision()->getMeeting()->getType();
    }

    public function asText(): string
    {
        return "Organ of type "
            . $this->getOrganType()->value
            . ' cannot be created in a meeting of type '
            . $this->getMeetingType()->value;
    }
}
