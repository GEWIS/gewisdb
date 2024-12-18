<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Application\Model\Enums\AppLanguages;
use Doctrine\ORM\Mapping\Entity;
use Laminas\Translator\TranslatorInterface;

/**
 * Abrogation of an organ.
 */
#[Entity]
class Abrogation extends FoundationReference
{
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->translate(
            '%ORGAN_TYPE% %ORGAN_ABBR% wordt opgeheven.',
            locale: $language->getLangParam(),
        );
    }

    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        $replacements = [
            '%ORGAN_TYPE%' => $this->getFoundation()->getOrganType()->getName($translator, $language),
            '%ORGAN_ABBR%' => $this->getFoundation()->getAbbr(),
        ];

        return $this->replaceContentPlaceholders($this->getTranslatedTemplate($translator, $language), $replacements);
    }
}
