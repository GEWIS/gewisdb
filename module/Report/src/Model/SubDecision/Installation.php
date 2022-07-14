<?php

namespace Report\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;
use Report\Model\SubDecision;
use Report\Model\Member;

/**
 * Installation into organ.
 *
 * @ORM\Entity
 */
class Installation extends FoundationReference
{
    /**
     * Function given.
     *
     * @ORM\Column(type="string")
     */
    protected $function;

    /**
     * Member.
     *
     * @ORM\ManyToOne(targetEntity="Report\Model\Member",inversedBy="installations")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Discharges.
     *
     * @ORM\OneToOne(targetEntity="Discharge",mappedBy="installation", cascade={"remove"})
     */
    protected $discharge;

    /**
     * The organmember reference.
     *
     * @ORM\OneToOne(targetEntity="Report\Model\OrganMember",mappedBy="installation", cascade={"remove"})
     */
    protected $organMember;


    /**
     * Get the function.
     *
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Set the function.
     *
     * @param string $function
     */
    public function setFunction($function)
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member)
    {
        $this->member = $member;
    }

    /**
     * Get the discharge, if it exists
     *
     * @return Discharge
     */
    public function getDischarge()
    {
        return $this->discharge;
    }

    /**
     * Clears the discharge, if it exists
     *
     * @return Discharge
     */
    public function clearDischarge()
    {
        $this->discharge = null;
    }

    /**
     * Get the organ member reference.
     */
    public function getOrganMember()
    {
        return $this->organMember;
    }
}
