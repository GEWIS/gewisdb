<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Financial;

use Application\Model\Enums\AppLanguages;
use Database\Model\SubDecision;
use Database\Model\Trait\FormattableDateTrait;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Laminas\Translator\TranslatorInterface;

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

    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->translate(
            'De begroting %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.',
            locale: $language->getLangParam(),
        );
    }

    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        $replacements = [
            '%NAME%' => $this->getName(),
            '%AUTHOR%' => null === $this->getMember()
                ? $translator->translate('onbekend', locale: $language->getLangParam())
                : $this->getMember()->getFullName(),
            '%VERSION%' => $this->getVersion(),
            '%DATE%' => $this->formatDate($this->getDate(), $language),
            '%APPROVAL%' => $this->getApproval()
                ? $translator->translate('goedgekeurd', locale: $language->getLangParam())
                : $translator->translate('afgekeurd', locale: $language->getLangParam()),
            '%CHANGES%' => $this->getApproval() && $this->getChanges()
                ? $translator->translate(' met genoemde wijzigingen', locale: $language->getLangParam())
                : '',
        ];

        return $this->replaceContentPlaceholders($this->getTranslatedTemplate($translator, $language), $replacements);
    }
}
