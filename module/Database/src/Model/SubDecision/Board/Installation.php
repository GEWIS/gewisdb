<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Board;

use Database\Model\Member;
use Database\Model\SubDecision;
use DateTime;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use IntlDateFormatter;
use Override;

use function date_default_timezone_get;

/**
 * Installation as board member.
 */
#[Entity]
#[AssociationOverrides([
    new AssociationOverride(
        name: 'member',
        joinColumns: new JoinColumn(
            name: 'lidnr',
            referencedColumnName: 'lidnr',
            nullable: false,
        ),
    ),
])]
class Installation extends SubDecision
{
    /**
     * Function in the board.
     */
    #[Column(type: 'string')]
    protected string $function;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    protected ?Discharge $discharge = null;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: 'installation',
    )]
    protected ?Release $release = null;

    /**
     * Get the function.
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Set the function.
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @psalm-suppress InvalidNullableReturnType
     */
    #[Override]
    public function getMember(): Member
    {
        return $this->member;
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
        return '%MEMBER% wordt per %DATE% geïnstalleerd als %FUNCTION% der s.v. GEWIS.';
    }

    protected function getAlternativeTemplate(): string
    {
        return '%MEMBER% is installed as %FUNCTION% of s.v. GEWIS effective from %DATE%.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%MEMBER%' => $this->getMember()->getFullName(),
            '%DATE%' => $this->formatDate($this->getDate()),
            '%FUNCTION%' => $this->getFunction(),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%MEMBER%' => $this->getMember()->getFullName(),
            '%DATE%' => $this->formatDate($this->getDate(), 'en_GB'),
            '%FUNCTION%' => $this->getFunction(), // Has no alternative (like the decision hash).
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
