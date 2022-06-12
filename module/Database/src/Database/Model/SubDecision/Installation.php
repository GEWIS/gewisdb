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
        $text = $member . ' wordt geïnstalleerd als ' . $this->getFunction();
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
