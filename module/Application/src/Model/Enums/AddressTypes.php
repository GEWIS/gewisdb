<?php

declare(strict_types=1);

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\Translator;

use function array_map;
use function array_merge;

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

    /**
     * @return array<array-key, AddressTypes|string>
     */
    public static function values(): array
    {
        return array_merge(
            array_map(
                static fn (self $status) => $status->value,
                self::cases(),
            ),
            self::cases(),
        );
    }
}
