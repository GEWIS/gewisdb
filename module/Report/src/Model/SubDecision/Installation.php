<?php

declare(strict_types=1);

namespace Report\Model\SubDecision;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Report\Model\Member;
use Report\Model\OrganMember;

/**
 * Installation into organ.
 */
#[Entity]
class Installation extends FoundationReference
{
    /**
     * Function given.
     */
    #[Column(type: 'string')]
    protected string $function;

    /**
     * Member.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'installations',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
    )]
    protected Member $member;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    protected ?Discharge $discharge = null;

    /**
     * The organmember reference.
     */
    #[OneToOne(
        targetEntity: OrganMember::class,
        mappedBy: 'installation',
    )]
    protected OrganMember $organMember;

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
     * Get the discharge, if it exists.
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
     * Get the organ member reference.
     */
    public function getOrganMember(): OrganMember
    {
        return $this->organMember;
    }
}
