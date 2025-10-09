<?php

declare(strict_types=1);

namespace Report\Model\SubDecision\Board;

use Database\Model\Enums\BoardFunctions;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use Report\Model\BoardMember;
use Report\Model\Member;
use Report\Model\SubDecision;
use Report\Model\Trait\MemberAwareTrait;

/**
 * Installation as board member.
 */
#[Entity]
class Installation extends SubDecision
{
    use MemberAwareTrait;

    /**
     * Function given.
     */
    #[Column(
        type: 'string',
        enumType: BoardFunctions::class,
    )]
    private BoardFunctions $function;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: 'date')]
    private DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    private ?Discharge $discharge = null;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: 'installation',
    )]
    private ?Release $release = null;

    /**
     * Board member reference.
     */
    #[OneToOne(
        targetEntity: BoardMember::class,
        mappedBy: 'installationDec',
    )]
    private BoardMember $boardMember;

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
