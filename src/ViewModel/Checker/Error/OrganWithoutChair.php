<?php

declare(strict_types=1);

namespace App\ViewModel\Checker\Error;

use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision\Foundation as FoundationModel;
use App\ViewModel\Checker\Error;
use Override;

use function sprintf;

/**
 * Error for when an organ that has members has nobody chairing it.
 *
 * Every organ is led by a chair; see the Articles of Association art. 22.3 and the Internal Regulations art. 11.3.1,
 * 13, and 16.
 *
 * @extends Error<FoundationModel>
 */
class OrganWithoutChair extends Error
{
    public function __construct(
        MeetingModel $meeting,
        FoundationModel $foundation,
    ) {
        parent::__construct(
            $meeting,
            $foundation,
        );
    }

    /**
     * Get the organ that has no chair.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision();
    }

    #[Override]
    public function asText(): string
    {
        return sprintf(
            '%s has no chair.',
            $this->getOrgan()->getAbbr(),
        );
    }
}
