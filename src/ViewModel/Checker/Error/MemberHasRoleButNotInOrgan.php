<?php

declare(strict_types=1);

namespace App\ViewModel\Checker\Error;

use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\Member as MemberModel;
use App\Entity\Database\SubDecision\Foundation as FoundationModel;
use App\Entity\Database\SubDecision\Installation as InstallationModel;
use App\ViewModel\Checker\Error;
use Override;

use function sprintf;

/**
 * Error for when a member has a special role in an organ but is not an (in)active member.
 *
 * @extends Error<InstallationModel>
 */
class MemberHasRoleButNotInOrgan extends Error
{
    /**
     * @param string $role Role that the member has in the organ
     */
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

    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Get the member who has a role but is not (in)active in the organ.
     */
    public function getMember(): MemberModel
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision()->getFoundation();
    }

    #[Override]
    public function asText(): string
    {
        return sprintf(
            'Member %s (%d) has a special role "%s" in %s but is not installed as "Lid".',
            $this->getMember()->getFullName(),
            $this->getMember()->getLidnr(),
            $this->getRole(),
            $this->getOrgan()->getName(),
        );
    }
}
