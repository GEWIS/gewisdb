<?php

namespace Database\Model\SubDecision\Board;

use Doctrine\ORM\Mapping as ORM;
use Database\Model\SubDecision;

/**
 * Discharge from board position.
 *
 * This decision references to an installation. The given installation is
 * 'undone' by this discharge.
 *
 * @ORM\Entity
 */
class Discharge extends SubDecision
{
    /**
     * Reference to the installation of a member.
     *
     * @ORM\OneToOne(targetEntity="Installation",inversedBy="discharge")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *  @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *  @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $installation;


    /**
     * Get installation.
     *
     * @return Installation
     */
    public function getInstallation()
    {
        return $this->installation;
    }

    /**
     * Set the installation.
     *
     * @param Installation $installation
     */
    public function setInstallation(Installation $installation)
    {
        $this->installation = $installation;
    }

    /**
     * Get the content.
     *
     * @return string
     */
    public function getContent()
    {
        $member = $this->getInstallation()->getMember()->getFullName();
        $function = $this->getInstallation()->getFunction();

        $text = $member . ' wordt gedechargeerd als ' . $function
              . ' der s.v. GEWIS.';
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
