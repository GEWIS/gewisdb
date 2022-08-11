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
 * Error for when an inactive member of an organ still has special roles.
 *
 * @extends Error<InstallationModel>
 */
class MemberInactiveInOrganButHasOtherRole extends Error
{
    public function __construct(
        MeetingModel $meeting,
        InstallationModel $installation,
        private readonly string $role,
    ) {
        parent::__construct(
            $meeting,
            $installation,
        );
    }

    /**
     * Get the role of the inactive member in the organ.
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Get the inactive member in the organ.
     */
    public function getMember(): MemberModel
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ with the inactive member who still has a role.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision()->getFoundation();
    }

    public function asText(): string
    {
        return sprintf(
            'Member %s (%d) is installed as "Inactief Lid" of %s but has a special role "%s".',
            $this->getMember()->getFullName(),
            $this->getMember()->getLidNr(),
            $this->getOrgan()->getName(),
            $this->getRole(),
        );
    }
}
