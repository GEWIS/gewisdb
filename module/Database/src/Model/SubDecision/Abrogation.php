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
    protected function getTemplate(): string
    {
        return '%ORGAN_TYPE% %ORGAN_ABBR% wordt opgeheven.';
    }

    protected function getAlternativeTemplate(): string
    {
        return '%ORGAN_TYPE% %ORGAN_ABBR% is abrogated.';
    }

    public function getContent(): string
    {
        $replacements = [
            '%ORGAN_TYPE%' => $this->getFoundation()->getOrganType()->getName(),
            '%ORGAN_ABBR%' => $this->getFoundation()->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getTemplate(), $replacements);
    }

    public function getAlternativeContent(): string
    {
        $replacements = [
            '%ORGAN_TYPE%' => $this->getFoundation()->getOrganType()->getAlternativeName(),
            '%ORGAN_ABBR%' => $this->getFoundation()->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getAlternativeTemplate(), $replacements);
    }
}
