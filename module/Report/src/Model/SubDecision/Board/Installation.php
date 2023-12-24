<?php

declare(strict_types=1);

namespace Report\Model\SubDecision\Board;

use DateTime;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Report\Model\BoardMember;
use Report\Model\Member;
use Report\Model\SubDecision;

/**
 * Installation as board member.
 */
#[Entity]
#[AssociationOverrides([
    new AssociationOverride(
        name: 'member',
        joinColumns: new JoinColumn(
            name: 'lidnr',
            referencedColumnName: 'lidnr',
            nullable: false,
        ),
    ),
])]
class Installation extends SubDecision
{
    /**
     * Function in the board.
     */
    #[Column(type: 'string')]
    protected string $function;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    protected ?Discharge $discharge = null;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: 'installation',
    )]
    protected ?Release $release = null;

    /**
     * Board member reference.
     */
    #[OneToOne(
        targetEntity: BoardMember::class,
        mappedBy: 'installationDec',
    )]
    protected BoardMember $boardMember;

    /**
     * Get the function.
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the function.
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @psalm-suppress InvalidNullableReturnType
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Get the date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the date.
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get the discharge.
     */
    public function getDischarge(): ?Discharge
    {
        return $this->discharge;
    }

    /**
     * Clears the discharge, if it exists.
     */
    public function clearDischarge(): void
    {
        $this->discharge = null;
    }

    /**
     * Get the release.
     */
    public function getRelease(): ?Release
    {
        return $this->release;
    }

    /**
     * Clears the release, if it exists.
     */
    public function clearRelease(): void
    {
        $this->release = null;
    }

    /**
     * Get the board member decision.
     */
    public function getBoardMember(): BoardMember
    {
        return $this->boardMember;
    }
}
