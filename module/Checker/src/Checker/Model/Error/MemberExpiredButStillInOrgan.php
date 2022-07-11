<?php

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\Meeting as MeetingModel;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;

class MemberExpiredButStillInOrgan extends Error
{
    /**
     * @param MeetingModel $meeting
     * @param InstallationModel $installation
     */
    public function __construct(
        MeetingModel $meeting,
        InstallationModel $installation
    ) {
        parent::__construct($meeting, $installation);
    }

    /**
     * @return MemberModel Member that is not member of GEWIS anymore
     */
    public function getMember()
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * @return FoundationModel Organ that the member is still a member of
     */
    public function getOrgan()
    {
        return $this->getSubDecision()->getFoundation();
    }

    public function asText()
    {
        return 'Member ' . $this->getMember()->getFullName() . ' is member of ' . $this->getOrgan()->getName()
        . ' however ' . $this->getMember()->getFullName() . ' is not a member of GEWIS anymore';
    }
}
