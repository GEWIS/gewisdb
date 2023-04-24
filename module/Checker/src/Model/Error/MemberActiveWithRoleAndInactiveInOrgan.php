<?php

declare(strict_types=1);

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
 * Error for when a member is "Inactief Lid" and "Lid" in an organ WITH special roles. We assume that the member should
 * be "Lid" and NOT "Inactief Lid".
 *
 * @extends Error<InstallationModel>
 */
class MemberActiveWithRoleAndInactiveInOrgan extends Error
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
     * Get the member who is installed as "Inactief Lid" and "Lid" with special roles.
     */
    public function getMember(): MemberModel
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ the member is installed in.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision()->getFoundation();
    }

    public function asText(): string
    {
        return sprintf(
            'Member %s (%d) is installed as "Lid" in %s with special roles but is also installed as "Inactief Lid".',
            $this->getMember()->getFullName(),
            $this->getMember()->getLidNr(),
            $this->getOrgan()->getName(),
        );
    }
}
