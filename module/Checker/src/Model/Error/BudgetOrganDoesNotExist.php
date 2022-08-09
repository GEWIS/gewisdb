<?php

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\SubDecision\{
    Budget as BudgetModel,
    Foundation as FoundationModel,
};

/**
 * Error for when a budget is created for an organ that does not exist.
 */
class BudgetOrganDoesNotExist extends Error
{
    public function __construct(BudgetModel $budget)
    {
        parent::__construct(
            $budget->getDecision()->getMeeting(),
            $budget,
        );
    }

    /**
     * Return the organ where this budget belongs to.
     */
    public function getOrgan(): FoundationModel
    {
        return $this->getSubDecision()->getFoundation();
    }

    public function asText(): string
    {
        return sprintf(
            'A budget for %s has been created, however, the organ does not exist.',
            $this->getOrgan()->getName(),
        );
    }
}
