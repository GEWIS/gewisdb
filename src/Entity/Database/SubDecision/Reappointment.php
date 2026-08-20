<?php

declare(strict_types=1);

namespace App\Entity\Database\SubDecision;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\SubDecision;
use App\Repository\Database\SubDecision\ReappointmentRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Reappointment of a previous installation.
 *
 * To prevent issues with recursive self-references, multiple reappointments can point to the same installation.
 */
#[Entity(repositoryClass: ReappointmentRepository::class)]
class Reappointment extends SubDecision
{
    /**
     * Reference to the installation of a member.
     */
    #[ManyToOne(
        targetEntity: Installation::class,
        inversedBy: 'reappointments',
    )]
    #[JoinColumn(
        name: 'r_meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'r_meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'r_decision_point',
        referencedColumnName: 'decision_point',
    )]
    #[JoinColumn(
        name: 'r_decision_number',
        referencedColumnName: 'decision_number',
    )]
    #[JoinColumn(
        name: 'r_sequence',
        referencedColumnName: 'sequence',
    )]
    private Installation $installation;

    /**
     * Get the original installation for this reappointment.
     */
    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    /**
     * Set the original installation for this reappointment.
     */
    public function setInstallation(Installation $installation): void
    {
        $this->installation = $installation;
    }

    #[Override]
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->trans(
            '%MEMBER% wordt herbenoemd als %FUNCTION% van %ORGAN_ABBR%.',
            locale: $language->getLangParam(),
        );
    }

    #[Override]
    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        $installation = $this->getInstallation();

        $replacements = [
            '%MEMBER%' => $installation->getMember()->getFullName(),
            '%FUNCTION%' => $installation->getFunction()->trans(
                $translator,
                $language->getLangParam(),
            ),
            '%ORGAN_ABBR%' => $installation->getFoundation()->getAbbr(),
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
