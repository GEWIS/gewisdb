<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Board;

use Database\Model\SubDecision;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Discharge from board position.
 *
 * This decision references to an installation. The given installation is
 * 'undone' by this discharge.
 */
#[Entity]
class Discharge extends SubDecision
{
    /**
     * Reference to the installation of a member.
     */
    #[OneToOne(
        targetEntity: Installation::class,
        inversedBy: 'discharge',
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
     * Get installation.
     */
    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    /**
     * Set the installation.
     */
    public function setInstallation(Installation $installation): void
    {
        $this->installation = $installation;
    }

    protected function getTemplate(): string
    {
        return '%MEMBER% wordt gedechargeerd als %FUNCTION% der s.v. GEWIS.';
    }

    protected function getAlternativeTemplate(): string
    {
        return '%MEMBER% is discharged as %FUNCTION% of s.v. GEWIS.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%MEMBER%' => $this->getInstallation()->getMember()->getFullName(),
            '%FUNCTION%' => $this->getInstallation()->getFunction(),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%MEMBER%' => $this->getInstallation()->getMember()->getFullName(),
            '%FUNCTION%' => $this->getInstallation()->getFunction(), // Has no alternative (like the decision hash).
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
