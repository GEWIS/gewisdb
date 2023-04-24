<?php

declare(strict_types=1);

namespace CheckerTest\Model\Error;

use Checker\Model\Error\MemberHasRoleButNotInOrgan;
use CheckerTest\Model\Error;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\{
    Foundation as FoundationModel,
    Installation as InstallationModel,
};

class MemberHasRoleButNotInOrganTest extends Error
{
    protected function create(): MemberHasRoleButNotInOrgan
    {
        $installation = new InstallationModel();
        $installation->setDecision($this->getDecision());
        $installation->setFoundation($this->getFoundation());
        $installation->setMember($this->getMember());

        $meeting = $this->getMeeting();

        return new MemberHasRoleButNotInOrgan($meeting, $installation, 'test');
    }

    public function testGetFoundation(): void
    {
        $error = $this->create();
        $this->assertInstanceOf(FoundationModel::class, $error->getOrgan());
    }

    public function testGetMember(): void
    {
        $error = $this->create();
        $this->assertInstanceOf(MemberModel::class, $error->getMember());
    }
}
