<?php

declare(strict_types=1);

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\Meeting as MeetingModel;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;

use function sprintf;

/**
 * Error for when a member is installed in an organ that either is not yet created, or already abrogated.
 *
 * @extends Error<InstallationModel>
 */
class MemberInNonExistingOrgan extends Error
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
     * Return the member that is in a non-existing organ.
     */
    public function getMember(): MemberModel
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ that does not exist anymore.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision()->getFoundation();
    }

    public function asText(): string
    {
        return sprintf(
            'Member %s (%d) is installed as "%s" in %s, which does not exist.',
            $this->getMember()->getFullName(),
            $this->getMember()->getLidnr(),
            $this->getSubDecision()->getFunction()->getName(null),
            $this->getOrgan()->getName(),
        );
    }
}
