<?php

declare(strict_types=1);

namespace App\Entity\Database\SubDecision;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision;
use App\Entity\Database\Traits\MemberAwareTrait;
use App\Repository\Database\SubDecision\MinutesRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;

/**
 * Decisions on minutes.
 */
#[Entity(repositoryClass: MinutesRepository::class)]
class Minutes extends SubDecision
{
    use MemberAwareTrait;

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
    private Meeting $meeting;

    /**
     * If the minutes were approved.
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

    #[Override]
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->trans(
            'De notulen van de %NUMBERORDINAL% %TYPE%%AUTHOR% worden %APPROVAL%%THANK%%CHANGES%.',
            locale: $language->getLangParam(),
        );
    }

    #[Override]
    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        $replacements = [
            '%TYPE%' => $this->getTarget()->getType()->value,
            '%NUMBERORDINAL%' => strval($this->getTarget()->getNumberAsOrdinal($language->getLocale())),
            '%APPROVAL%' => $this->getApproval()
                ? $translator->trans(
                    'goedgekeurd',
                    locale: $language->getLangParam(),
                )
                : $translator->trans(
                    'afgekeurd',
                    locale: $language->getLangParam(),
                ),
            '%AUTHOR%' => MeetingTypes::BV === $this->getTarget()->getType()
                ? ''
                : $translator->trans(
                    ' door ',
                    locale: $language->getLangParam(),
                )
                    . $this->getMember()->getFullName(),
            '%CHANGES%' => $this->getApproval() && $this->getChanges()
                ? $translator->trans(
                    ' met genoemde wijzigingen',
                    locale: $language->getLangParam(),
                )
                : '',
            '%THANK%' => MeetingTypes::BV === $this->getTarget()->getType()
                ? $translator->trans(
                    ' met dank aan de notulist',
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
