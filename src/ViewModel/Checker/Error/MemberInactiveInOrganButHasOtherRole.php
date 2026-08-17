<?php

declare(strict_types=1);

namespace App\ViewModel\Checker\Error;

use App\Entity\Decision\Meeting as MeetingModel;
use App\Entity\Decision\SubDecision\Foundation as FoundationModel;
use App\Entity\Decision\SubDecision\Installation as InstallationModel;
use App\Entity\Member\Member as MemberModel;
use App\ViewModel\Checker\Error;
use Override;

use function sprintf;

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

    #[Override]
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
