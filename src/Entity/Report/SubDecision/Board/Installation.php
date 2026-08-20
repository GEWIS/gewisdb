<?php

declare(strict_types=1);

namespace App\Entity\Report\SubDecision\Board;

use App\Entity\Database\Enums\BoardFunctions;
use App\Entity\Report\BoardMember;
use App\Entity\Report\Member;
use App\Entity\Report\SubDecision;
use App\Entity\Report\Traits\MemberAwareTrait;
use App\Repository\Report\SubDecision\Board\InstallationRepository;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Installation as board member.
 */
#[Entity(repositoryClass: InstallationRepository::class)]
class Installation extends SubDecision
{
    use MemberAwareTrait;

    /**
     * Function given.
     */
    #[Column(
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
     * Set the board member decision.
     *
     * Kept in step with the owning side, so that a board member only just derived from this installation can be found
     * right away, without having to go through the database for it.
     */
    public function setBoardMember(BoardMember $boardMember): void
    {
        $this->boardMember = $boardMember;
    }

    /**
     * Forget what was derived from this subdecision, because it no longer exists.
     *
     * Leaves the property uninitialised again, which is how the rest of the code recognises that there is nothing.
     */
    public function clearBoardMember(): void
    {
        unset($this->boardMember);
    }

    /**
     * Get the board member decision.
     */
    public function getBoardMember(): BoardMember
    {
        return $this->boardMember;
    }
}
