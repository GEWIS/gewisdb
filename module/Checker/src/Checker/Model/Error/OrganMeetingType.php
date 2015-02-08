<?php
namespace Checker\Model\Error;

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
class OrganMeetingType extends \Checker\Model\Error {

    public function __construct(
        \Database\Model\SubDecision\Foundation $foundation
    ) {
        parent::__construct($foundation->getDecision()->getMeeting(), $foundation);
        $this->organType = $foundation->getOrganType();
        $this->meetingType = $foundation->getMeetingType();
    }


    /**
     * @return string Type of organ that was created
     */
    public function getOrganType()
    {
        return $this->getSubDecision()->getOrganType();
    }

    /**
     * @return string Type of meeting that this organ was created
     */
    public function getMeetingType()
    {

        return $this->getSubDecision()->getDecision()->getMeeting()->getType();
    }

    public function asText() {
        return "Organ of type " . $this->getOrganType() . ' can not be created in a meeting of type ' . $this->getMeetingType();
    }
} 