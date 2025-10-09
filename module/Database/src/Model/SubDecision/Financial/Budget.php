<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Financial;

use Application\Model\Enums\AppLanguages;
use Database\Model\Member;
use Database\Model\SubDecision;
use Database\Model\Trait\FormattableDateTrait;
use Database\Model\Trait\MemberAwareTrait;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Laminas\Translator\TranslatorInterface;

#[Entity]
class Budget extends SubDecision
{
    use FormattableDateTrait;
    use MemberAwareTrait;

    /**
     * Name of the budget.
     */
    #[Column(type: 'string')]
    private string $name;

    /**
     * Version of the budget.
     */
    #[Column(
        type: 'string',
        length: 32,
    )]
    private string $version;

    /**
     * Date of the budget.
     */
    #[Column(type: 'date')]
    private DateTime $date;

    /**
     * If the budget was approved.
     */
    #[Column(type: 'boolean')]
    private bool $approval;

    /**
     * If there were changes made.
     */
    #[Column(type: 'boolean')]
    private bool $changes;

    /**
     * Get the member.
     *
     * @psalm-suppress InvalidNullableReturnType
     */
    public function getMember(): Member
    {
        return $this->member;
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
            '%AUTHOR%' => $this->getMember()->getFullName(),
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
