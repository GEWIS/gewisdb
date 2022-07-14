<?php

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different membership types as defined in the bylaws of the association.
 */
enum MembershipTypes: string
{
    case Ordinary = 'ordinary';
    case External = 'external';
    case Graduate = 'graduate';
    case Honorary = 'honorary';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Ordinary => $translator->translate('Gewoon'),
            self::External => $translator->translate('Extern'),
            self::Graduate => $translator->translate('Afgestudeerde'),
            self::Honorary => $translator->translate('Erelid'),
        };
    }
}
