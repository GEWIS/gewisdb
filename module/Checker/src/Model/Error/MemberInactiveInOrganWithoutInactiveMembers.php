<?php

declare(strict_types=1);

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\Meeting as MeetingModel;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;
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
            $this->getOrgan()->getOrganType()->getName(null),
        );
    }
}
