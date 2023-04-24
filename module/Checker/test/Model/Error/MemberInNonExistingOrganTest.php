<?php

declare(strict_types=1);

namespace CheckerTest\Model\Error;

use Checker\Model\Error\MemberInNonExistingOrgan;
use CheckerTest\Model\Error;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;

class MemberInNonExistingOrganTest extends Error
{
    protected function create(): MemberInNonExistingOrgan
    {
        $installation = new InstallationModel();
        $installation->setDecision($this->getDecision());
        $installation->setFoundation($this->getFoundation());
        $installation->setMember($this->getMember());
        $installation->setNumber(1);
        $installation->setFunction("Tester");

        $meeting = $this->getMeeting();

        return new MemberInNonExistingOrgan($meeting, $installation);
    }

    public function testGetFoundation(): void
    {
        $error = $this->create();
        $this->assertInstanceOf(FoundationModel::class, $error->getOrgan());
    }
}
