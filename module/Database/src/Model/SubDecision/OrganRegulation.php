<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Application\Model\Enums\OrganTypes;
use Database\Model\Member;
use Database\Model\SubDecision;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use IntlDateFormatter;

use function date_default_timezone_get;
use function str_replace;

#[Entity]
class OrganRegulation extends SubDecision
{
    /**
     * OrganRegulation author.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    protected ?Member $author = null;

    /**
     * Name of the organ.
     */
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Type of the organ.
     */
    #[Column(
        type: 'string',
        enumType: OrganTypes::class,
    )]
    protected OrganTypes $organType;

    /**
     * Version of the regulation.
     */
    #[Column(
        type: 'string',
        length: 32,
    )]
    protected string $version;

    /**
     * Date of the regulation.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * If the regulation was approved.
     */
    #[Column(type: 'boolean')]
    protected bool $approval;

    /**
     * If there were changes made.
     */
    #[Column(type: 'boolean')]
    protected bool $changes;

    /**
     * Get the author.
     */
    public function getAuthor(): ?Member
    {
        return $this->author;
    }

    /**
     * Set the author.
     */
    public function setAuthor(Member $author): void
    {
        $this->author = $author;
    }

    /**
     * Set the organ type
     */
    public function setOrganType(OrganTypes $organType): void
    {
        $this->organType = $organType;
    }

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set the version.
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
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
     * Get approval status.
     */
    public function getApproval(): bool
    {
        return $this->approval;
    }

    /**
     * Set approval status.
     */
    public function setApproval(bool $approval): void
    {
        $this->approval = $approval;
    }

    /**
     * Get if changes were made.
     */
    public function getChanges(): bool
    {
        return $this->changes;
    }

    /**
     * Set if changes were made.
     */
    public function setChanges(bool $changes): void
    {
        $this->changes = $changes;
    }

    /**
     * Get the content.
     */
    public function getContent(): string
    {
        $template = $this->getTemplate();
        $template = str_replace('%NAME%', $this->getName(), $template);
        if (null === $this->getAuthor()) {
            $template = str_replace('%AUTHOR%', 'onbekend', $template);
        } else {
            $template = str_replace('%AUTHOR%', $this->getAuthor()->getFullName(), $template);
        }

        $template = str_replace('%VERSION%', $this->getVersion(), $template);
        $template = str_replace('%DATE%', $this->formatDate($this->getDate()), $template);
        if ($this->getApproval()) {
            $template = str_replace('%APPROVAL%', 'goedgekeurd', $template);
            if ($this->getChanges()) {
                $template = str_replace('%CHANGES%', ' met genoemde wijzigingen', $template);
            } else {
                $template = str_replace('%CHANGES%', '', $template);
            }
        } else {
            $template = str_replace('%APPROVAL%', 'afgekeurd', $template);
            $template = str_replace('%CHANGES%', '', $template);
        }

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
            'nl_NL', // yes, hardcoded :D
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            date_default_timezone_get(),
            null,
            'd MMMM Y',
        );

        return $formatter->format($date);
    }

    /**
     * Decision template
     */
    protected function getTemplate(): string
    {
        return 'Het commissiereglement %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.';
    }
}
