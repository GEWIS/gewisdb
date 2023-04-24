<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Key;

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
use function str_replace;

#[Entity]
class Withdrawal extends SubDecision
{
    /**
     * Reference to the granting of a keycode.
     */
    #[OneToOne(
        targetEntity: Granting::class,
        inversedBy: "withdrawal",
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
    protected Granting $granting;

    /**
     * When the granted keycode is prematurely revoked.
     */
    #[Column(type: "date")]
    protected DateTime $withdrawnOn;

    /**
     * Get the granting of the keycode.
     *
     * @return Granting
     */
    public function getGranting(): Granting
    {
        return $this->granting;
    }

    /**
     * Set the granting of the keycode.
     */
    public function setGranting(Granting $granting): void
    {
        $this->granting = $granting;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getWithdrawnOn(): DateTime
    {
        return $this->withdrawnOn;
    }

    /**
     * Set the date.
     *
     * @param DateTime $withdrawnOn
     */
    public function setWithdrawnOn(DateTime $withdrawnOn): void
    {
        $this->withdrawnOn = $withdrawnOn;
    }

    /**
     * Get the content.
     *
     * @return string
     */
    public function getContent(): string
    {
        $template = $this->getTemplate();

        $template = str_replace('%GRANTEE%', $this->getGranting()->getGrantee()->getFullName(), $template);
        $template = str_replace('%WITHDRAWAL%', $this->formatDate($this->getWithdrawnOn()), $template);

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
        return 'De sleutelcode van %GRANTEE% van GEWIS wordt ingetrokken vanaf %WITHDRAWAL%.';
    }
}
