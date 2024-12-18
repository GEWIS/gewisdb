<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Application\Model\Enums\AppLanguages;
use Application\Model\Enums\MeetingTypes;
use Database\Model\Meeting;
use Database\Model\SubDecision;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\I18n\Translator\TranslatorInterface;

use function strval;

/**
 * Decisions on minutes.
 */
#[Entity]
class Minutes extends SubDecision
{
    /**
     * Reference to the meeting.
     */
    #[OneToOne(
        targetEntity: Meeting::class,
        inversedBy: 'minutes',
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'number',
    )]
    protected Meeting $meeting;

    /**
     * If the minutes were approved.
     */
    #[Column(type: 'boolean')]
    protected bool $approval;

    /**
     * If there were changes made.
     */
    #[Column(type: 'boolean')]
    protected bool $changes;

    /**
     * Get the target.
     */
    public function getTarget(): Meeting
    {
        return $this->meeting;
    }

    /**
     * Set the target.
     */
    public function setTarget(Meeting $meeting): void
    {
        $this->meeting = $meeting;
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
            'De notulen van de %NUMBERORDINAL% %TYPE%%AUTHOR% worden %APPROVAL%%THANK%%CHANGES%.',
            locale: $language->getLangParam(),
        );
    }

    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        $replacements = [
            '%TYPE%' => $this->getTarget()->getType()->value,
            '%NUMBERORDINAL%' => strval($this->getTarget()->getNumberAsOrdinal($language->getLocale())),
            '%APPROVAL%' => $this->getApproval()
                ? $translator->translate('goedgekeurd', locale: $language->getLangParam())
                : $translator->translate('afgekeurd', locale: $language->getLangParam()),
            '%AUTHOR%' => MeetingTypes::BV === $this->getTarget()->getType()
                ? ''
                : $translator->translate(' door ', locale: $language->getLangParam())
                    . $this->getMember()->getFullName(),
            '%CHANGES%' => $this->getApproval() && $this->getChanges()
                ? $translator->translate(' met genoemde wijzigingen', locale: $language->getLangParam())
                : '',
            '%THANK%' => MeetingTypes::BV === $this->getTarget()->getType()
                ? $translator->translate(' met dank aan de notulist', locale: $language->getLangParam())
                : '',
        ];

        return $this->replaceContentPlaceholders($this->getTranslatedTemplate($translator, $language), $replacements);
    }
}
