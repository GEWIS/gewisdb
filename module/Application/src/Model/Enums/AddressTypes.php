<?php

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different address types.
 */
enum AddressTypes: string
{
    case Home = 'home';
    case Student = 'student';
    case Mail = 'mail';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Home => $translator->translate('Home Address (Parents)'),
            self::Student => $translator->translate('Student Address'),
            self::Mail => $translator->translate('Mail Address'),
        };
    }

    public static function values(): array
    {
        return array_merge(
            array_map(
                fn (self $status) => $status->value,
                self::cases(),
            ),
            self::cases(),
        );
    }
}
