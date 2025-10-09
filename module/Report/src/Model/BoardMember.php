<?php

declare(strict_types=1);

namespace Report\Model;

use Database\Model\Enums\BoardFunctions;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Report\Model\SubDecision\Board\Installation as BoardInstallation;

/**
 * Board member entity.
 *
 * Note that this entity is derived from the decisions themself.
 */
#[Entity]
class BoardMember
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * Member lidnr.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'boardInstallations',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private Member $member;

    /**
     * Function given.
     */
    #[Column(
        type: 'string',
        enumType: BoardFunctions::class,
    )]
    private BoardFunctions $function;

    /**
     * Installation date.
     */
    #[Column(type: 'date')]
    private DateTime $installDate;

    /**
     * Installation.
     */
    #[OneToOne(
        targetEntity: BoardInstallation::class,
        inversedBy: 'boardMember',
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'r_decision_point',
        referencedColumnName: 'decision_point',
    )]
    #[JoinColumn(
        name: 'r_decision_number',
        referencedColumnName: 'decision_number',
    )]
    #[JoinColumn(
        name: 'r_sequence',
        referencedColumnName: 'sequence',
    )]
    private BoardInstallation $installationDec;

    /**
     * Release date.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    private ?DateTime $releaseDate = null;

    /**
     * Discharge date.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    private ?DateTime $dischargeDate = null;

    /**
     * Get the ID.
     *
     * @psalm-ignore-nullable-return
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the member.
     */
    public function getMember(): Member
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
     * Get the function.
     */
    public function getFunction(): BoardFunctions
    {
        return $this->function;
    }

    /**
     * Set the function.
     */
    public function setFunction(BoardFunctions $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the installation date.
     */
    public function getInstallDate(): DateTime
    {
        return $this->installDate;
    }

    /**
     * Set the installation date.
     */
    public function setInstallDate(DateTime $installDate): void
    {
        $this->installDate = $installDate;
    }

    /**
     * Get the installation decision.
     */
    public function getInstallationDec(): BoardInstallation
    {
        return $this->installationDec;
    }

    /**
     * Set the installation decision.
     */
    public function setInstallationDec(BoardInstallation $installationDec): void
    {
        $this->installationDec = $installationDec;
    }

    /**
     * Get the release date.
     */
    public function getReleaseDate(): ?DateTime
    {
        return $this->releaseDate;
    }

    /**
     * Set the release date.
     */
    public function setReleaseDate(?DateTime $releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    /**
     * Get the discharge date.
     */
    public function getDischargeDate(): ?DateTime
    {
        return $this->dischargeDate;
    }

    /**
     * Set the discharge date.
     */
    public function setDischargeDate(?DateTime $dischargeDate): void
    {
        $this->dischargeDate = $dischargeDate;
    }
}
