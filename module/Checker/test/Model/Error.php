<?php

namespace CheckerTest\Model;

use Application\Model\Enums\{
    MeetingTypes,
    MembershipTypes,
    OrganTypes,
};
use Database\Model\{
    Decision as DecisionModel,
    Meeting as MeetingModel,
    Member as MemberModel,
    SubDecision as SubDecisionModel,
};
use Database\Model\SubDecision\Foundation as FoundationModel;
use DateTime;
use PHPUnit\Framework\TestCase;

abstract class Error extends TestCase
{
    // Create a new error
    abstract protected function create();

    public function getMeeting(): MeetingModel
    {
        $meeting = new MeetingModel();
        $meeting->setType(MeetingTypes::ALV);
        $meeting->setNumber(1);

        return $meeting;
    }

    protected function getDecision(): DecisionModel
    {
        $decision = new DecisionModel();
        $decision->setMeeting($this->getMeeting());
        $decision->setNumber(1);
        $decision->setPoint(1);

        return $decision;
    }

    protected function getFoundation(): FoundationModel
    {
        $foundation = new FoundationModel();
        $foundation->setDecision($this->getDecision());
        $foundation->setNumber(1);
        $foundation->setAbbr('AT');
        $foundation->setName('A Test');
        $foundation->setOrganType(OrganTypes::Committee);

        return $foundation;
    }

    protected function getMember(): MemberModel
    {
        $member = new MemberModel();
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
        $this->assertInstanceOf(SubDecisionModel::class, $error->getSubDecision());
    }

    public function testGetMeeting()
    {
        $error = $this->create();
        $this->assertInstanceOf(MeetingModel::class, $error->getMeeting());
    }

    public function testAsText()
    {
        $error = $this->create();
        $this->assertTrue(is_string($error->asText()));
    }
}
