<?php

declare(strict_types=1);

namespace App\Entity\Decision\SubDecision\Financial;

use App\Entity\Application\Enums\AppLanguages;
use Doctrine\ORM\Mapping\Entity;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Entity]
class Statement extends Budget
{
    #[Override]
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->trans(
            'De afrekening %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.',
            locale: $language->getLangParam(),
        );
    }
}
