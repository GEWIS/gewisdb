<?php

namespace Report\Model\SubDecision\Board;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
    OneToOne,
};
use Report\Model\{
    BoardMember,
    Member,
    SubDecision,
};

/**
 * Installation as board member.
 */
#[Entity]
class Installation extends SubDecision
{
    /**
     * Function in the board.
     */
    #[Column(type: "string")]
    protected string $function;

    /**
     * Member.
     *
     * Note that only members that are older than 18 years can be board members.
     * Also, honorary, external and extraordinary members cannot be board members.
     * (See the Statuten, Art. 13 Lid 2.
     */
    // TODO: Inversed relation
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
    )]
    protected Member $member;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: "date")]
    protected DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: "installation",
    )]
    protected ?Discharge $discharge = null;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: "installation",
    )]
    protected ?Release $release = null;

    /**
     * Board member reference.
     */
    #[OneToOne(
        targetEntity: BoardMember::class,
        mappedBy: "installationDec",
    )]
    protected BoardMember $boardMember;

    /**
     * Get the function.
     *
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the function.
     *
     * @param string $function
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the date.
     *
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get the discharge.
     *
     * @return ?Discharge
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
     *
     * @return ?Release
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
     *
     * @return BoardMember
     */
    public function getBoardMember(): BoardMember
    {
        return $this->boardMember;
    }
}
