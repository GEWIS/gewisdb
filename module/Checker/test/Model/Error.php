<?php

namespace CheckerTest\Model;

use Application\Model\Enums\MeetingTypes;
use Application\Model\Enums\MembershipTypes;
use Application\Model\Enums\OrganTypes;
use Database\Model\Decision;
use Database\Model\Meeting;
use Database\Model\Member;
use Database\Model\SubDecision\Foundation;
use DateTime;
use PHPUnit\Framework\TestCase;

abstract class Error extends TestCase
{
    // Create a new error
    abstract protected function create();

    public function getMeeting()
    {
        $meeting = new Meeting();
        $meeting->setType(MeetingTypes::AV);
        $meeting->setNumber(1);
        return $meeting;
    }

    protected function getDecision()
    {
        $decision = new Decision();
        $decision->setMeeting($this->getMeeting());
        $decision->setNumber(1);
        $decision->setPoint(1);
        return $decision;
    }

    protected function getFoundation()
    {
        $foundation = new Foundation();
        $foundation->setDecision($this->getDecision());
        $foundation->setNumber(1);
        $foundation->setAbbr('AT');
        $foundation->setName('A Test');
        $foundation->setOrganType(OrganTypes::Committee);
        return $foundation;
    }

    protected function getMember()
    {
        $member = new Member();
        $member->setType(MembershipTypes::Ordinary);
        $member->setFirstName("Anton");
        $member->setMiddleName("");
        $member->setLastName("Antonius");
        $member->setEmail("anton.antonius@gewis.nl");
        $member->setBirth(new DateTime());
        return $member;
    }

    public function testGetSubDecision()
    {
        $error = $this->create();
        $this->assertInstanceOf('Database\Model\SubDecision', $error->getSubDecision());
    }

    public function testGetMeeting()
    {
        $error = $this->create();
        $this->assertInstanceOf('Database\Model\Meeting', $error->getMeeting());
    }

    public function testAsText()
    {
        $error = $this->create();
        $this->assertTrue(is_string($error->asText()));
    }
}
