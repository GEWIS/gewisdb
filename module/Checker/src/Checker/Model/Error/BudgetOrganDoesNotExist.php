<?php

namespace Checker\Model\Error;

/**
 * Class BudgetOrganDoesNotExist
 *
 * This class denotes an error where a budget is created for an organ that does not exist
 *
 * @package Checker\Model\Error
 */
class BudgetOrganDoesNotExist extends \Checker\Model\Error
{
    public function __construct(\Database\Model\SubDecision\Budget $budget)
    {
        parent::__construct($budget->getDecision()->getMeeting(), $budget);
    }

    public function asText()
    {
        return 'Budget from foundation has been created. However foundation does not exist';
    }
}
