<?php
namespace CheckerTest\Model\Error;

use \Checker\Model\Error\BudgetOrganDoesNotExist as BudgetOrganDoesNotExistError;
use Database\Model\SubDecision\Budget;

class BudgetOrganDoesNotExistTest extends \CheckerTest\Model\Error
{
    protected function create() {
        $budget = new Budget();
        $budget->setDecision($this->getDecision());

        return new BudgetOrganDoesNotExistError($budget);
    }

    public function testGetFoundation()
    {
        $error = $this->create();
    }
}
