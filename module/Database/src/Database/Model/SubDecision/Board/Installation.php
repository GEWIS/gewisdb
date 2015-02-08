<?php

namespace Database\Model\SubDecision\Board;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;
use Database\Model\Member;

/**
 * Installation as board member.
 *
 * @ORM\Entity
 */
class Installation extends SubDecision
{

    /**
     * Function in the board.
     *
     * @ORM\Column(type="string")
     */
    protected $function;

    /**
     * Member.
     *
     * Note that only members that are older than 18 years can be board members.
     * Also, honorary, external and extraordinary members cannot be board members.
     * (See the Statuten, Art. 13 Lid 2.
     *
     * @todo Inversed relation
     *
     * @ORM\ManyToOne(targetEntity="Database\Model\Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * The date at which the installation is in effect.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * Discharge.
     *
     * @ORM\OneToOne(targetEntity="Discharge",mappedBy="installation")
     */
    protected $discharge;

    /**
     * Release.
     *
     * @ORM\OneToOne(targetEntity="Release",mappedBy="installation")
     */
    protected $release;


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
     * Get the date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the date.
     *
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
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
        $text = $member . ' wordt per ' . $this->formatDate($this->getDate())
              . ' gëinstalleerd als ' . $this->getFunction() . ' der s.v. GEWIS.';
        return $text;
    }

    /**
     * Format the date.
     *
     * returns the localized version of $date->format('d F Y')
     *
     * @param DateTime $date
     *
     * @return string Formatted date
     */
    protected function formatDate(\DateTime $date)
    {
        $formatter = new \IntlDateFormatter(
            'nl_NL', // yes, hardcoded :D
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            \date_default_timezone_get(),
            null,
            'd MMMM Y'
        );
        return $formatter->format($date);
    }
}
