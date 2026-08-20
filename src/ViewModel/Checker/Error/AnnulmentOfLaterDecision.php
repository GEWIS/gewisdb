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
 * Error for when a decision annuls a decision that was taken after it.
 *
 * GEWISDB is a ledger, so it cannot be rewritten from the past: whatever an annulment takes back must already have
 * happened by the time the annulment is made.
 *
 * @extends Error<AnnulmentModel>
 */
class AnnulmentOfLaterDecision extends Error
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
     * Get the decision that is annulled before it was taken.
     */
    public function getTarget(): DecisionModel
    {
        return $this->getSubDecision()->getTarget();
    }

    #[Override]
    public function asText(): string
    {
        return sprintf(
            'Decision %s annuls %s, which was only taken afterwards.',
            $this->getSubDecision()->getDecision()->getHash(),
            $this->getTarget()->getHash(),
        );
    }
}
