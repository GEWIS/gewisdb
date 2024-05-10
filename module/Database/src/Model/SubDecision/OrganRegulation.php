<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Application\Model\Enums\OrganTypes;
use Database\Model\SubDecision;
use Database\Model\Trait\FormattableDateTrait;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use ValueError;

#[Entity]
class OrganRegulation extends SubDecision
{
    use FormattableDateTrait;

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
     * Get the type.
     */
    public function getOrganType(): OrganTypes
    {
        return $this->organType;
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

    protected function getTemplate(): string
    {
        return 'Het %TYPE%reglement van %NAME% door %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.';
    }

    protected function getAlternativeTemplate(): string
    {
        return 'The %TYPE%regulations of %NAME% by %AUTHOR%, version %VERSION% dated %DATE% are %APPROVAL%%CHANGES%.';
    }

    /**
     * Get the content.
     */
    public function getContent(): string
    {
        if (OrganTypes::Committee === $this->getOrganType()) {
            $organType = 'commissie';
        } elseif (OrganTypes::Fraternity === $this->getOrganType()) {
            $organType = 'dispuuts';
        } else {
            throw new ValueError();
        }

        $replacements = [
            '%NAME%' => $this->getName(),
            '%AUTHOR%' => null === $this->getMember() ? 'onbekend' : $this->getMember()->getFullName(),
            '%TYPE%' => $organType,
            '%VERSION%' => $this->getVersion(),
            '%DATE%' => $this->formatDate($this->getDate()),
            '%APPROVAL%' => $this->getApproval() ? 'goedgekeurd' : 'afgekeurd',
            '%CHANGES%' => $this->getApproval() && $this->getChanges() ? ' met genoemde wijzigingen' : '',
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        if (OrganTypes::Committee === $this->getOrganType()) {
            $organType = 'committee ';
        } elseif (OrganTypes::Fraternity === $this->getOrganType()) {
            $organType = 'fraternity ';
        } else {
            throw new ValueError();
        }

        $replacements = [
            '%NAME%' => $this->getName(),
            '%AUTHOR%' => null === $this->getMember() ? 'unknown' : $this->getMember()->getFullName(),
            '%TYPE%' => $organType,
            '%VERSION%' => $this->getVersion(),
            '%DATE%' => $this->formatDate($this->getDate(), 'en_GB'),
            '%APPROVAL%' => $this->getApproval() ? 'approved' : 'disapproved',
            '%CHANGES%' => $this->getApproval() && $this->getChanges() ? ' with mentioned changes' : '',
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
