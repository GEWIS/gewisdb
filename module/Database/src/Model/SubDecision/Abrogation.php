<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping\Entity;

/**
 * Abrogation of an organ.
 */
#[Entity]
class Abrogation extends FoundationReference
{
    /**
     * Get the content.
     *
     * @todo implement this
     *
     * @return string
     */
    public function getContent(): string
    {
        // <type> <abbr> wordt opgeheven.
        return $this->getFoundation()->getOrganType()->getName() . ' '
            . $this->getFoundation()->getAbbr() . ' wordt opgeheven.';
    }
}
