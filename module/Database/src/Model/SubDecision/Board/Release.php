<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Board;

use Database\Model\SubDecision;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
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
        inversedBy: 'release',
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'r_decision_point',
        referencedColumnName: 'decision_point',
    )]
    #[JoinColumn(
        name: 'r_decision_number',
        referencedColumnName: 'decision_number',
    )]
    #[JoinColumn(
        name: 'r_number',
        referencedColumnName: 'number',
    )]
    protected Installation $installation;

    /**
     * Date of the discharge.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * Get installation.
     */
    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    /**
     * Set the installation.
     */
    public function setInstallation(Installation $installation): void
    {
        $this->installation = $installation;
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
     * Format the date.
     *
     * returns the localized version of $date->format('d F Y')
     *
     * @return string Formatted date
     */
    protected function formatDate(
        DateTime $date,
        string $locale = 'nl_NL',
    ): string {
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            date_default_timezone_get(),
            null,
            'd MMMM y',
        );

        return $formatter->format($date);
    }

    protected function getTemplate(): string
    {
        return '%MEMBER% wordt per %DATE% ontheven uit de functie van %FUNCTION% der s.v. GEWIS.';
    }

    protected function getAlternativeTemplate(): string
    {
        return '%MEMBER% is relieved from the position of %FUNCTION% of s.v. GEWIS effective from %DATE%.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%MEMBER%' => $this->getInstallation()->getMember()->getFullName(),
            '%DATE%' => $this->formatDate($this->date),
            '%FUNCTION%' => $this->getInstallation()->getFunction(),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%MEMBER%' => $this->getInstallation()->getMember()->getFullName(),
            '%DATE%' => $this->formatDate($this->date, 'en_GB'),
            '%FUNCTION%' => $this->getInstallation()->getFunction(), // Has no alternative (like the decision hash).
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
