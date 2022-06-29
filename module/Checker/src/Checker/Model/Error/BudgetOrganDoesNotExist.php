<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 8-2-15
 * Time: 11:40
 */

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\SubDecision\Budget as BudgetModel;
use Database\Model\SubDecision\Foundation as FoundationModel;

/**
 * Class BudgetOrganDoesNotExist
 *
 * This class denotes an error where a budget is created for an organ that does not exist
 *
 * @package Checker\Model\Error
 */
class BudgetOrganDoesNotExist extends Error
{
    public function __construct(BudgetModel $budget) {
        parent::__construct($budget->getDecision()->getMeeting(), $budget);
    }

    /**
     * Return the foundation where this budget belongs to
     *
     * @return FoundationModel
     */
    public function getFoundation()
    {
        return $this->getSubDecision()->getFoundation();
    }

    public function asText()
    {
        return 'Budget from ' . $this->getFoundation()->getName() . ' has been created. However '
        . $this->getFoundation()->getName() . ' does not exist';
    }
}
