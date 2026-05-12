<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Financial;

use Application\Model\Enums\AppLanguages;
use Doctrine\ORM\Mapping\Entity;
use Laminas\Translator\TranslatorInterface;
use Override;

#[Entity]
class Statement extends Budget
{
    #[Override]
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->translate(
            'De afrekening %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.',
            locale: $language->getLangParam(),
        );
    }
}
