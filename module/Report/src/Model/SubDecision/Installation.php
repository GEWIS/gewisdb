<?php

namespace Report\Model\SubDecision;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
    OneToOne,
};
use Report\Model\{
    Member,
    OrganMember,
};

/**
 * Installation into organ.
 */
#[Entity]
class Installation extends FoundationReference
{
    /**
     * Function given.
     */
    #[Column(type: "string")]
    protected string $function;

    /**
     * Member.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: "installations",
    )]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
    )]
    protected Member $member;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: "installation",
    )]
    protected ?Discharge $discharge = null;

    /**
     * The organmember reference.
     */
    #[OneToOne(
        targetEntity: OrganMember::class,
        mappedBy: "installation",
    )]
    protected OrganMember $organMember;

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
     * Get the discharge, if it exists.
     *
     * @return Discharge|null
     */
    public function getDischarge(): ?Discharge
    {
        return $this->discharge;
    }

    /**
     * Get the organ member reference.
     *
     * @return OrganMember
     */
    public function getOrganMember(): OrganMember
    {
        return $this->organMember;
    }
}
