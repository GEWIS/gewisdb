<?php
namespace Checker\Model\Error;


use Checker\Model\Error;
use Database\Model\Meeting as MeetingModel;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;

/**
 * Class MemberInNonExistingOrgan
 *
 * Denotes an error where a member is installed in an organ that either
 * is not yet created, or is abrogated.
 *
 * @package Checker\Model\Error
 */
class MemberInNonExistingOrgan extends Error
{
    public function __construct(
        MeetingModel $meeting,
        InstallationModel $installation
    ) {
        parent::__construct($meeting, $installation);
    }

    /**
     * Return the member that is in a non existing organ
     *
     * @return MemberModel
     */
    public function getMember()
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ that does not exist anymore
     *
     * @return FoundationModel
     */
    public function getFoundation()
    {
        return $this->getSubDecision()->getFoundation();
    }


    public function asText()
    {
        return 'Member ' . $this->getMember()->getFullName()
            .    ' ('. $this->getMember()->getLidNr() . ')'
            . ' is still installed as '. $this->getSubDecision()->getFunction() . ' in '
            . $this->getFoundation()->getName() . ' which does not exist anymore';
    }
}
