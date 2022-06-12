<?php

namespace CheckerTest\Model\Error;

use Checker\Model\Error\MemberInNonExistingOrgan;
use Database\Model\SubDecision\Installation;

class MemberInNonExistingOrganTest extends \CheckerTest\Model\Error
{
    protected function create()
    {
        $installation = new Installation();
        $installation->setDecision($this->getDecision());
        $installation->setFoundation($this->getFoundation());
        $installation->setMember($this->getMember());

        $meeting = $this->getMeeting();

        return new MemberInNonExistingOrgan($meeting, $installation);
    }

    public function testGetFoundation()
    {
        $error = $this->create();
        $this->assertInstanceOf('Database\Model\SubDecision\Foundation', $error->getFoundation());
    }
}
