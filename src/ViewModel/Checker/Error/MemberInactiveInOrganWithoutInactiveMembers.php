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
 * Error for when someone is an inactive member of an organ that does not have those.
 *
 * Only fraternities keep members who no longer study (Internal Regulations art. 13); every other organ discharges
 * whoever is no longer part of it.
 *
 * @extends Error<InstallationModel>
 */
class MemberInactiveInOrganWithoutInactiveMembers extends Error
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
     * Get the member that is inactive.
     */
    public function getMember(): MemberModel
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ that does not have inactive members.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision()->getFoundation();
    }

    #[Override]
    public function asText(): string
    {
        return sprintf(
            'Member %s (%d) is an inactive member of %s, which is a %s and does not have those.',
            $this->getMember()->getFullName(),
            $this->getMember()->getLidnr(),
            $this->getOrgan()->getAbbr(),
            $this->getOrgan()->getOrganType()->getName()->getMessage(),
        );
    }
}
