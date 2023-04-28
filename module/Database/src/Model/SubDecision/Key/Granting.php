<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Key;

use Database\Model\Member;
use Database\Model\SubDecision;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use IntlDateFormatter;

use function date_default_timezone_get;
use function str_replace;

#[Entity]
class Granting extends SubDecision
{
    /**
     * The member who is granted a keycode of GEWIS.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    protected ?Member $grantee = null;

    /**
     * Till when the keycode is granted.
     */
    #[Column(type: 'date')]
    protected DateTime $until;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Withdrawal::class,
        mappedBy: 'granting',
    )]
    protected ?Withdrawal $withdrawal = null;

    /**
     * Get the grantee.
     */
    public function getGrantee(): ?Member
    {
        return $this->grantee;
    }

    /**
     * Set the grantee.
     */
    public function setGrantee(Member $grantee): void
    {
        $this->grantee = $grantee;
    }

    /**
     * Get the date.
     */
    public function getUntil(): DateTime
    {
        return $this->until;
    }

    /**
     * Set the date.
     */
    public function setUntil(DateTime $until): void
    {
        $this->until = $until;
    }

    /**
     * Get the content.
     */
    public function getContent(): string
    {
        $template = $this->getTemplate();

        $template = str_replace('%GRANTEE%', $this->getGrantee()->getFullName(), $template);
        $template = str_replace('%UNTIL%', $this->formatDate($this->getUntil()), $template);

        return $template;
    }

    /**
     * Format the date.
     *
     * returns the localized version of $date->format('d F Y')
     *
     * @return string Formatted date
     */
    protected function formatDate(DateTime $date): string
    {
        $formatter = new IntlDateFormatter(
            'nl_NL',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            date_default_timezone_get(),
            null,
            'd MMMM Y',
        );

        return $formatter->format($date);
    }

    /**
     * Decision template.
     */
    protected function getTemplate(): string
    {
        return '%GRANTEE% krijgt een sleutelcode van GEWIS tot en met %UNTIL%.';
    }
}
