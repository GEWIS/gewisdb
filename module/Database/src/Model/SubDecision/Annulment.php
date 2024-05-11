<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Database\Model\Decision;
use Database\Model\SubDecision;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Annulling a decision.
 *
 * This decision references to a different decision. The given decision is
 * annulled, as if it did never exist.
 *
 * Note that this behaviour might not always work flawlessly. It is very
 * complicated, and thus there might be edge cases that I didn't completely
 * catch. If that is the case, let me know!
 *
 * Also note that annulling decisions that annul is undefined behaviour!
 */
#[Entity]
class Annulment extends SubDecision
{
    /**
     * Reference to the annulment of a decision.
     */
    #[OneToOne(
        targetEntity: Decision::class,
        inversedBy: 'annulledBy',
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
        referencedColumnName: 'point',
    )]
    #[JoinColumn(
        name: 'r_decision_number',
        referencedColumnName: 'number',
    )]
    protected Decision $target;

    /**
     * Get the target.
     */
    public function getTarget(): Decision
    {
        return $this->target;
    }

    /**
     * Set the target.
     */
    public function setTarget(Decision $target): void
    {
        $this->target = $target;
    }

    protected function getTemplate(): string
    {
        return 'Besluit %DECISION_HASH% wordt nietig verklaard. Het besluit luidde: "%DECISION_CONTENT%"';
    }

    protected function getAlternativeTemplate(): string
    {
        return 'Decision %DECISION_HASH% is annulled. The decision read: "%DECISION_CONTENT%"';
    }

    public function getContent(): string
    {
        $replacements = [
            '%DECISION_HASH%' => $this->getTarget()->getHash(),
            '%DECISION_CONTENT%' => $this->getTarget()->getContent(),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%DECISION_HASH%' => $this->getTarget()->getHash(), // We do not provide an alternative to the hash.
            '%DECISION_CONTENT%' => $this->getTarget()->getAlternativeContent(),
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
