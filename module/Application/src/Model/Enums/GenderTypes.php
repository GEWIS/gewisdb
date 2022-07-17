<?php

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different address types.
 */
enum GenderTypes: string
{
    case Female = 'f';
    case Male = 'm';
    case Other = 'o';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Female => $translator->translate('Vrouw'),
            self::Male => $translator->translate('Man'),
            self::Other => $translator->translate('Anders'),
        };
    }
}
