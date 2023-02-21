<?php

namespace Database\Model\SubDecision\Key;

use Database\Model\{
    Member,
    SubDecision,
};
use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
    OneToOne,
};
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
        name: "lidnr",
        referencedColumnName: "lidnr",
        nullable: true,
    )]
    protected ?Member $grantee = null;

    /**
     * Till when the keycode is granted.
     */
    #[Column(type: "date")]
    protected DateTime $until;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Withdrawal::class,
        mappedBy: "granting",
    )]
    protected ?Withdrawal $withdrawal = null;

    /**
     * Get the grantee.
     *
     * @return Member|null
     */
    public function getGrantee(): ?Member
    {
        return $this->grantee;
    }

    /**
     * Set the grantee.
     *
     * @param Member $grantee
     */
    public function setGrantee(Member $grantee): void
    {
        $this->grantee = $grantee;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getUntil(): DateTime
    {
        return $this->until;
    }

    /**
     * Set the date.
     *
     * @param DateTime $until
     */
    public function setUntil(DateTime $until): void
    {
        $this->until = $until;
    }

    /**
     * Get the content.
     *
     * @return string
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
     * @param DateTime $date
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
     *
     * @return string
     */
    protected function getTemplate(): string
    {
        return '%GRANTEE% krijgt een sleutelcode van GEWIS tot en met %UNTIL%.';
    }
}
