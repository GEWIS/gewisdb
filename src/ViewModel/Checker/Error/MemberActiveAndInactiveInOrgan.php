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
 * Error for when a member is "Inactief Lid" and "Lid" in an organ WITHOUT any special roles. We assume that the member
 * should NOT be "Lid".
 *
 * @extends Error<InstallationModel>
 */
class MemberActiveAndInactiveInOrgan extends Error
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
     * Get the member who is installed as "Inactief Lid" and "Lid" but without any special roles.
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

    #[Override]
    public function asText(): string
    {
        return sprintf(
            'Member %s (%d) is marked as "Inactief Lid" of %s but is still a "Lid".',
            $this->getMember()->getFullName(),
            $this->getMember()->getLidNr(),
            $this->getOrgan()->getName(),
        );
    }
}
