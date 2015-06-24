<?php
namespace CheckerTest\Model\Error;

use \Checker\Model\Error\BudgetOrganDoesNotExist as BudgetOrganDoesNotExistError;
use Checker\Model\Error\MemberHasRoleButNotInOrgan;
use Database\Model\SubDecision\Budget;
use Database\Model\SubDecision\Installation;

class MemberHasRoleButNotInOrganTest extends \CheckerTest\Model\Error
{
    protected function create() {
        $installation = new Installation();
        $installation->setDecision($this->getDecision());
        $installation->setFoundation($this->getFoundation());
        $installation->setMember($this->getMember());

        $meeting = $this->getMeeting();

        return new MemberHasRoleButNotInOrgan($meeting, $installation, 'test');
    }

    public function testGetFoundation()
    {
        $error = $this->create();
        $this->assertInstanceOf('Database\Model\SubDecision\Foundation', $error->getFoundation());
    }

    public function testGetMember()
    {
        $error = $this->create();
        $this->assertInstanceOf('Database\Model\Member', $error->getMember());
    }

} 