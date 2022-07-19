<?php

namespace Database\Model\SubDecision\Board;

use Database\Model\SubDecision;
use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    OneToOne,
};
use IntlDateFormatter;

use function date_default_timezone_get;

/**
 * Release from board duties.
 *
 * This decision references to an installation. The duties of this installation
 * are released by this release.
 */
#[Entity]
class Release extends SubDecision
{
    /**
     * Reference to the installation of a member.
     */
    #[OneToOne(
        targetEntity: Installation::class,
        inversedBy: "release",
    )]
    #[JoinColumn(
        name: "r_meeting_type",
        referencedColumnName: "meeting_type",
    )]
    #[JoinColumn(
        name: "r_meeting_number",
        referencedColumnName: "meeting_number",
    )]
    #[JoinColumn(
        name: "r_decision_point",
        referencedColumnName: "decision_point",
    )]
    #[JoinColumn(
        name: "r_decision_number",
        referencedColumnName: "decision_number",
    )]
    #[JoinColumn(
        name: "r_number",
        referencedColumnName: "number",
    )]
    protected Installation $installation;

    /**
     * Date of the discharge.
     */
    #[Column(type: "date")]
    protected DateTime $date;

    /**
     * Get installation.
     *
     * @return Installation
     */
    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    /**
     * Set the installation.
     *
     * @param Installation $installation
     */
    public function setInstallation(Installation $installation): void
    {
        $this->installation = $installation;
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
     * Get the content.
     *
     * @return string
     */
    public function getContent(): string
    {
        $member = $this->getInstallation()->getMember()->getFullName();
        $function = $this->getInstallation()->getFunction();

        return $member . ' wordt per ' . $this->formatDate($this->getDate())
              . ' ontheven uit de functie van ' . $function
              . ' der s.v. GEWIS.';
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
    protected function formatDate(DateTime $date): string
    {
        $formatter = new IntlDateFormatter(
            'nl_NL', // yes, hardcoded :D
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            date_default_timezone_get(),
            null,
            'd MMMM Y',
        );

        return $formatter->format($date);
    }
}
