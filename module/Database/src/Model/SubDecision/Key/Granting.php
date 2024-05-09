<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Key;

use Database\Model\SubDecision;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use IntlDateFormatter;

use function date_default_timezone_get;

#[Entity]
class Granting extends SubDecision
{
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
        return '%GRANTEE% krijgt een sleutelcode van GEWIS tot en met %UNTIL%.';
    }

    /**
     * Note: whether "until" is inclusive or exclusive is ambiguous. However, the wording in the actual key policy is
     * also ambiguous. As such, it was decided that the actual decision (in Dutch) will always be inclusive as indicated
     * by "tot en met".
     */
    protected function getAlternativeTemplate(): string
    {
        return '%GRANTEE% is granted a key code of GEWIS until %UNTIL%.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%GRANTEE%' => $this->getMember()->getFullName(),
            '%UNTIL%' => $this->formatDate($this->getUntil()),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%GRANTEE%' => $this->getMember()->getFullName(),
            '%UNTIL%' => $this->formatDate($this->getUntil(), 'en_GB'),
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
