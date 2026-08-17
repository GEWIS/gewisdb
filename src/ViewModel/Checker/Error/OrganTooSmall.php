<?php

declare(strict_types=1);

namespace App\ViewModel\Checker\Error;

use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision\Foundation as FoundationModel;
use App\ViewModel\Checker\Error;
use Override;

use function sprintf;

/**
 * Error for when an organ has fewer members than its type is allowed to have.
 *
 * A fraternity needs "tenminste 3 actieve dispuutsleden" (Internal Regulations art. 13.8) and a GMM taskforce
 * "tenminste 3 leden" (art. 16.5); every other organ needs at least someone in it.
 *
 * @extends Error<FoundationModel>
 */
class OrganTooSmall extends Error
{
    public function __construct(
        MeetingModel $meeting,
        FoundationModel $foundation,
        private readonly int $members,
    ) {
        parent::__construct(
            $meeting,
            $foundation,
        );
    }

    /**
     * Get the organ that is too small.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision();
    }

    /**
     * Get the number of members the organ does have.
     */
    public function getMembers(): int
    {
        return $this->members;
    }

    #[Override]
    public function asText(): string
    {
        return sprintf(
            '%s has %d active member(s), while a %s needs at least %d.',
            $this->getOrgan()->getAbbr(),
            $this->getMembers(),
            $this->getOrgan()->getOrganType()->getName()->getMessage(),
            $this->getOrgan()->getOrganType()->getMinimumMembers(),
        );
    }
}
