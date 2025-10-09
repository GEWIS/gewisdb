<?php

declare(strict_types=1);

namespace Report\Model\SubDecision\Board;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Report\Model\SubDecision;

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
        name: 'r_sequence',
        referencedColumnName: 'sequence',
    )]
    private Installation $installation;

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
}
