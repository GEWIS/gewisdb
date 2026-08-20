<?php

declare(strict_types=1);

namespace App\ViewModel\Checker\Error;

use App\Entity\Database\Decision as DecisionModel;
use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision\Annulment as AnnulmentModel;
use App\ViewModel\Checker\Error;
use Override;

use function sprintf;

/**
 * Error for when a decision that annuls another decision is itself annulled.
 *
 * An annulment brings nothing about of its own, so taking one back has no meaning; the annulling decision should be
 * deleted instead, which restores what it annulled.
 *
 * @extends Error<AnnulmentModel>
 */
class AnnulmentOfAnnulment extends Error
{
    public function __construct(
        MeetingModel $meeting,
        AnnulmentModel $annulment,
    ) {
        parent::__construct(
            $meeting,
            $annulment,
        );
    }

    /**
     * Get the decision that annuls another decision and is annulled itself.
     */
    public function getTarget(): DecisionModel
    {
        return $this->getSubDecision()->getTarget();
    }

    #[Override]
    public function asText(): string
    {
        return sprintf(
            'Decision %s annuls %s, which annuls another decision; an annulment cannot be annulled.',
            $this->getSubDecision()->getDecision()->getHash(),
            $this->getTarget()->getHash(),
        );
    }
}
