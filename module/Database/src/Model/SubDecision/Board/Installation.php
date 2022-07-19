<?php

namespace Database\Model\SubDecision\Board;

use Database\Model\{
    SubDecision,
    Member,
};
use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    JoinColumn,
    ManyToOne,
    OneToOne,
    Entity,
};
use IntlDateFormatter;

use function date_default_timezone_get;

/**
 * Installation as board member.
 */
#[Entity]
class Installation extends SubDecision
{
    /**
     * Function in the board.
     */
    #[Column(type: "string")]
    protected string $function;

    /**
     * Member.
     *
     * Note that only members that are older than 18 years can be board members.
     * Also, honorary members cannot be board members.
     * (See the Statuten, Art. 13A Lid 2.
     *
     * @todo Inversed relation
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
    )]
    protected Member $member;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: "date")]
    protected DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: "installation",
    )]
    protected Discharge $discharge;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: "installation",
    )]
    protected Release $release;

    /**
     * Get the function.
     *
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the function.
     *
     * @param string $function
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
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
     * Fixes Bor's greatest frustration
     *
     * @return string
     */
    public function getContent(): string
    {
        $member = $this->getMember()->getFullName();
        return $member . ' wordt per ' . $this->formatDate($this->getDate())
              . ' geïnstalleerd als ' . $this->getFunction() . ' der s.v. GEWIS.';
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
