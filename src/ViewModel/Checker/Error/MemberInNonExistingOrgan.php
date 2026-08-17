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

    #[Override]
    public function asText(): string
    {
        return sprintf(
            'Member %s (%d) is installed as "%s" in %s, which does not exist.',
            $this->getMember()->getFullName(),
            $this->getMember()->getLidnr(),
            $this->getSubDecision()->getFunction()->getName()->getMessage(),
            $this->getOrgan()->getName(),
        );
    }
}
