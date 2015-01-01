<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;
use Database\Model\Member;

/**
 * Installation into organ.
 *
 * @ORM\Entity
 */
class Installation extends FoundationReference
{
    const FUNC_MEMBER = 'member';
    const FUNC_CHAIRMAN = 'chairman';
    const FUNC_SECRETARY = 'secretary';
    const FUNC_TREASURER = 'treasurer';
    const FUNC_VICE_CHAIRMAN = 'vice-chairman';
    const FUNC_PR_OFFICER = 'pr-officer';
    const FUNC_EDUCATION_OFFICER = 'education-officer';

    /**
     * Function given.
     *
     * @ORM\Column(type="string")
     */
    protected $function;

    /**
     * Member.
     *
     * @ORM\ManyToOne(targetEntity="Database\Model\Member",inversedBy="installations")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Discharges.
     *
     * @ORM\OneToOne(targetEntity="Discharge",mappedBy="installation")
     */
    protected $discharge;


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
     * Get the content.
     *
     * Fixes Bor's greatest frustration
     *
     * @return string
     */
    public function getContent()
    {
        $member = $this->getMember()->getFullName();
        $text = $member . ' wordt gëinstalleerd als ' . $this->getFunction();
        $text .= ' van ' . $this->getFoundation()->getAbbr() . '.';
        return $text;
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
}
