<?php

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\{
    Meeting as MeetingModel,
    Member as MemberModel,
};
use Database\Model\SubDecision\{
    Foundation as FoundationModel,
    Installation as InstallationModel,
};

/**
 * Error for when a member is installed in an organ while their GEWIS membership has expired.
 *
 * @extends Error<InstallationModel>
 */
class MemberExpiredButStillInOrgan extends Error
{
    public function __construct(
        MeetingModel $meeting,
        InstallationModel $installation,
    ) {
        parent::__construct(
            $meeting,
            $installation,
        );
    }

    /**
     * Get the member who is no longer a member of GEWIS (i.e., their expiry has lapsed).
     */
    public function getMember(): MemberModel
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ that the member is still installed in.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision()->getFoundation();
    }

    public function asText(): string
    {
        return sprintf(
            'Member %s (%d) is installed in %s, however, their GEWIS membership has expired.',
            $this->getMember()->getFullName(),
            $this->getMember()->getLidnr(),
            $this->getOrgan()->getName(),
        );
    }
}
