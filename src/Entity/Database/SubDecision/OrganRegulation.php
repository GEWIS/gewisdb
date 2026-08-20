<?php

declare(strict_types=1);

namespace App\Entity\Database\SubDecision;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Application\Traits\FormattableDateTrait;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision;
use App\Entity\Database\Traits\MemberAwareTrait;
use App\Repository\Database\SubDecision\OrganRegulationRepository;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;

#[Entity(repositoryClass: OrganRegulationRepository::class)]
class OrganRegulation extends SubDecision
{
    use FormattableDateTrait;
    use MemberAwareTrait;

    /**
     * Abbreviation of the organ.
     */
    #[Column(type: 'string')]
    private string $abbr;

    /**
     * Type of the organ.
     */
    #[Column(
        enumType: OrganTypes::class,
    )]
    private OrganTypes $organType;

    /**
     * Version of the regulation.
     */
    #[Column(
        type: 'string',
        length: 32,
    )]
    private string $version;

    /**
     * Date of the regulation.
     */
    #[Column(type: 'date')]
    private DateTime $date;

    /**
     * If the regulation was approved.
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
     * Get the abbreviation.
     */
    public function getAbbr(): string
    {
        return $this->abbr;
    }

    /**
     * Set the abbreviation.
     */
    public function setAbbr(string $abbr): void
    {
        $this->abbr = $abbr;
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

    #[Override]
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->trans(
            'Het %DOCUMENTTYPE% van %NAME% door %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.',
            locale: $language->getLangParam(),
        );
    }

    #[Override]
    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        if (
            OrganTypes::Committee === $this->getOrganType()
            || OrganTypes::KCC === $this->getOrganType()
        ) {
            $documentType = $translator->trans(
                'commissiereglement',
                locale: $language->getLangParam(),
            );
        } elseif (OrganTypes::Fraternity === $this->getOrganType()) {
            $documentType = $translator->trans(
                'dispuutsreglement',
                locale: $language->getLangParam(),
            );
        } else {
            throw new ValueError();
        }

        $replacements = [
            '%NAME%' => $this->getAbbr(),
            '%AUTHOR%' => $this->getMember()->getFullName(),
            '%DOCUMENTTYPE%' => $documentType,
            '%VERSION%' => $this->getVersion(),
            '%DATE%' => $this->formatDate(
                $this->getDate(),
                $language,
            ),
            '%APPROVAL%' => $this->getApproval()
                ? $translator->trans(
                    'goedgekeurd',
                    locale: $language->getLangParam(),
                )
                : $translator->trans(
                    'afgekeurd',
                    locale: $language->getLangParam(),
                ),
            '%CHANGES%' => $this->getApproval() && $this->getChanges()
                ? $translator->trans(
                    ' met genoemde wijzigingen',
                    locale: $language->getLangParam(),
                )
                : '',
        ];

        return $this->replaceContentPlaceholders(
            $this->getTranslatedTemplate(
                $translator,
                $language,
            ),
            $replacements,
        );
    }
}
