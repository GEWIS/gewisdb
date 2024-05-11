<?php

declare(strict_types=1);

namespace Report\Model;

use Application\Model\Enums\MeetingTypes;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Report\Model\SubDecision\Abrogation;
use Report\Model\SubDecision\Board\Discharge as BoardDischarge;
use Report\Model\SubDecision\Board\Installation as BoardInstallation;
use Report\Model\SubDecision\Board\Release as BoardRelease;
use Report\Model\SubDecision\Budget;
use Report\Model\SubDecision\Destroy;
use Report\Model\SubDecision\Discharge;
use Report\Model\SubDecision\Foundation;
use Report\Model\SubDecision\FoundationReference;
use Report\Model\SubDecision\Installation;
use Report\Model\SubDecision\Key\Granting as KeyGranting;
use Report\Model\SubDecision\Key\Withdrawal as KeyWithdrawal;
use Report\Model\SubDecision\OrganRegulation;
use Report\Model\SubDecision\Other;
use Report\Model\SubDecision\Reappointment;
use Report\Model\SubDecision\Reckoning;

/**
 * SubDecision model.
 */
#[Entity]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'organ_regulation' => OrganRegulation::class,
        'foundation' => Foundation::class,
        'abrogation' => Abrogation::class,
        'installation' => Installation::class,
        'reappointment' => Reappointment::class,
        'discharge' => Discharge::class,
        'budget' => Budget::class,
        'reckoning' => Reckoning::class,
        'other' => Other::class,
        'destroy' => Destroy::class,
        'board_installation' => BoardInstallation::class,
        'board_release' => BoardRelease::class,
        'board_discharge' => BoardDischarge::class,
        'foundationreference' => FoundationReference::class,
        'key_granting' => KeyGranting::class,
        'key_withdraw' => KeyWithdrawal::class,
    ],
)]
abstract class SubDecision
{
    /**
     * Decision.
     */
    #[ManyToOne(
        targetEntity: Decision::class,
        inversedBy: 'subdecisions',
    )]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'decision_point',
        referencedColumnName: 'point',
    )]
    #[JoinColumn(
        name: 'decision_number',
        referencedColumnName: 'number',
    )]
    protected Decision $decision;

    /**
     * Meeting type.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(
        type: 'string',
        enumType: MeetingTypes::class,
    )]
    protected MeetingTypes $meeting_type;

    /**
     * Meeting number.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $meeting_number;

    /**
     * Decision point.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $decision_point;

    /**
     * Decision number.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $decision_number;

    /**
     * Sub decision sequence number.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $sequence;

    /**
     * Content.
     */
    #[Column(type: 'text')]
    protected string $content;

    /**
     * The member involved in this sub-decision.
     *
     * Not all sub-decisions require this, as such it is nullable. However, sub-decisions that need the guarantee that
     * this is not null or need to specify an inverse side can do so using an association override.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    protected ?Member $member = null;

    /**
     * Get the decision.
     */
    public function getDecision(): Decision
    {
        return $this->decision;
    }

    /**
     * Set the decision.
     */
    public function setDecision(Decision $decision): void
    {
        $decision->addSubdecision($this);
        $this->meeting_type = $decision->getMeetingType();
        $this->meeting_number = $decision->getMeetingNumber();
        $this->decision_point = $decision->getPoint();
        $this->decision_number = $decision->getNumber();
        $this->decision = $decision;
    }

    /**
     * Get the meeting type.
     */
    public function getMeetingType(): MeetingTypes
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     */
    public function getMeetingNumber(): int
    {
        return $this->meeting_number;
    }

    /**
     * Get the decision point number.
     */
    public function getDecisionPoint(): int
    {
        return $this->decision_point;
    }

    /**
     * Get the decision number.
     */
    public function getDecisionNumber(): int
    {
        return $this->decision_number;
    }

    /**
     * Get the sequence number.
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * Set the sequence number.
     */
    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * Get the member.
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the content.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
