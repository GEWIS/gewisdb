<?php

declare(strict_types=1);

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\DummyTranslator;
use Laminas\Mvc\I18n\Translator;

use function array_map;
use function array_merge;

/**
 * Enum for the different address types.
 */
enum MeetingTypes: string
{
    case BV = 'BV'; // bestuursvergadering
    case ALV = 'ALV'; // algemene leden vergadering
    case VV = 'VV'; // voorzitters vergadering
    case VIRT = 'Virt'; // virtual meeting

    /**
     * @return array<array-key, MeetingTypes|string>
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

    /**
     * Give the function name with the given translation. If no translator is given, we return the default language.
     */
    public function getName(
        ?Translator $translator,
        ?AppLanguages $language = null,
    ): string {
        if (null === $translator) {
            $translator = new DummyTranslator();
        }

        $function = match ($this) {
            self::BV => $translator->translate('BV', locale: $language?->getLangParam()),
            self::ALV => $translator->translate('ALV', locale: $language?->getLangParam()),
            self::VV => $translator->translate('VV', locale: $language?->getLangParam()),
            self::VIRT => $translator->translate('Virt', locale: $language?->getLangParam()),
        };

        return $translator->translate($function, locale: $language?->getLangParam());
    }
}
