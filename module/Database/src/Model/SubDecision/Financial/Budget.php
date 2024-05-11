<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Financial;

use Database\Model\SubDecision;
use Database\Model\Trait\FormattableDateTrait;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Budget extends SubDecision
{
    use FormattableDateTrait;

    /**
     * Name of the budget.
     */
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Version of the budget.
     */
    #[Column(
        type: 'string',
        length: 32,
    )]
    protected string $version;

    /**
     * Date of the budget.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * If the budget was approved.
     */
    #[Column(type: 'boolean')]
    protected bool $approval;

    /**
     * If there were changes made.
     */
    #[Column(type: 'boolean')]
    protected bool $changes;

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
        return 'De begroting %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.';
    }

    protected function getAlternativeTemplate(): string
    {
        return 'The budget %NAME% by %AUTHOR%, version %VERSION% dated %DATE% is %APPROVAL%%CHANGES%.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%NAME%' => $this->getName(),
            '%AUTHOR%' => null === $this->getMember() ? 'onbekend' : $this->getMember()->getFullName(),
            '%VERSION%' => $this->getVersion(),
            '%DATE%' => $this->formatDate($this->getDate()),
            '%APPROVAL%' => $this->getApproval() ? 'goedgekeurd' : 'afgekeurd',
            '%CHANGES%' => $this->getApproval() && $this->getChanges() ? ' met genoemde wijzigingen' : '',
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%NAME%' => $this->getName(),
            '%AUTHOR%' => null === $this->getMember() ? 'unknown' : $this->getMember()->getFullName(),
            '%VERSION%' => $this->getVersion(),
            '%DATE%' => $this->formatDate($this->getDate(), 'en_GB'),
            '%APPROVAL%' => $this->getApproval() ? 'approved' : 'disapproved',
            '%CHANGES%' => $this->getApproval() && $this->getChanges() ? ' with mentioned changes' : '',
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
