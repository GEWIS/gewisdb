<?php

declare(strict_types=1);

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Annulment as AnnulmentModel;
use Override;

use function sprintf;

/**
 * Error for when more than one decision annuls the same decision.
 *
 * The second one has nothing left to take back, and deleting either of them would restore a decision that the other
 * still annuls.
 *
 * @extends Error<AnnulmentModel>
 */
class DecisionAnnulledMoreThanOnce extends Error
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
     * Get the decision that is annulled more than once.
     */
    public function getTarget(): DecisionModel
    {
        return $this->getSubDecision()->getTarget();
    }

    #[Override]
    public function asText(): string
    {
        return sprintf(
            'Decision %s annuls %s, which was already annulled by another decision.',
            $this->getSubDecision()->getDecision()->getHash(),
            $this->getTarget()->getHash(),
        );
    }
}
