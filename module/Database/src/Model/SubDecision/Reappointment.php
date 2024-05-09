<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Database\Model\SubDecision;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Reappointment of a previous installation.
 *
 * To prevent issues with recursive self-references, multiple reappointments can point to the same installation.
 */
#[Entity]
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
        name: 'r_number',
        referencedColumnName: 'number',
    )]
    protected Installation $installation;

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

    protected function getTemplate(): string
    {
        return '%MEMBER% wordt herbenoemd als %FUNCTION% van %ORGAN_ABBR%.';
    }

    protected function getAlternativeTemplate(): string
    {
        return '%MEMBER% is reappointed as %FUNCTION% of %ORGAN_ABBR%.';
    }

    public function getContent(): string
    {
        $installation = $this->getInstallation();

        $replacements = [
            '%MEMBER%' => $installation->getMember()->getFullName(),
            '%FUNCTION%' => $installation->getFunction(),
            '%ORGAN_ABBR%' => $installation->getFoundation()->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $installation = $this->getInstallation();

        $replacements = [
            '%MEMBER%' => $installation->getMember()->getFullName(),
            '%FUNCTION%' => $installation->getFunction(), // Has no alternative (like the decision hash).
            '%ORGAN_ABBR%' => $installation->getFoundation()->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
