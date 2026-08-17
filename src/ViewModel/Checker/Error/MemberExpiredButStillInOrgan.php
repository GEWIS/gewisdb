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

    #[Override]
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
