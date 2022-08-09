<?php

namespace CheckerTest\Model\Error;

use Checker\Model\Error\BudgetOrganDoesNotExist as BudgetOrganDoesNotExistError;
use CheckerTest\Model\Error;
use Database\Model\SubDecision\Budget as BudgetModel;

class BudgetOrganDoesNotExistTest extends Error
{
    protected function create(): BudgetOrganDoesNotExistError
    {
        $budget = new BudgetModel();
        $budget->setDecision($this->getDecision());

        return new BudgetOrganDoesNotExistError($budget);
    }

    public function testGetFoundation()
    {
        $error = $this->create();
    }

    public function testAsText()
    {
        $this->markTestSkipped("This test case is obsolete for this type");
    }
}
