<?php

declare(strict_types=1);

namespace CheckerTest\Model;

use Application\Model\Enums\MeetingTypes;
use Application\Model\Enums\MembershipTypes;
use Application\Model\Enums\OrganTypes;
use Checker\Model\Error as CheckerErrorModel;
use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;
use Database\Model\Member as MemberModel;
use Database\Model\Membership as MembershipModel;
use Database\Model\SubDecision as SubDecisionModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use DateTime;
use PHPUnit\Framework\TestCase;

use function is_string;

abstract class Error extends TestCase
{
    /**
     * Create a new error
     */
    abstract protected function create(): CheckerErrorModel;

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
        $foundation->setSequence(1);
        $foundation->setAbbr('AT');
        $foundation->setName('A Test');
        $foundation->setOrganType(OrganTypes::Committee);

        return $foundation;
    }

    protected function getMember(): MemberModel
    {
        $member = new MemberModel();
        $member->setLidnr(1);
        $member->setFirstName('Anton');
        $member->setMiddleName('');
        $member->setLastName('Antonius');
        $member->setEmail('anton.antonius@gewis.nl');
        $member->setBirth(new DateTime());

        $membership = new MembershipModel(
            member: $member,
            type: MembershipTypes::Ordinary,
            startDate: (new DateTime())->setDate(2020, 7, 1),
            endDate: (new DateTime())->setDate(2021, 7, 1),
        );
        $member->addMembership($membership);

        return $member;
    }

    public function testGetSubDecision(): void
    {
        $error = $this->create();
        $this->assertInstanceOf(SubDecisionModel::class, $error->getSubDecision());
    }

    public function testGetMeeting(): void
    {
        $error = $this->create();
        $this->assertInstanceOf(MeetingModel::class, $error->getMeeting());
    }

    public function testAsText(): void
    {
        $error = $this->create();
        $this->assertTrue(is_string($error->asText()));
    }
}
