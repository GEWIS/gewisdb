<?php

declare(strict_types=1);

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\DummyTranslator;
use Laminas\Mvc\I18n\Translator;

use function array_combine;
use function array_map;
use function in_array;

/**
 * Enum for the different organ types.
 */
enum OrganTypes: string
{
    case Committee = 'committee';
    case AVC = 'avc';
    case Fraternity = 'fraternity';
    case KCC = 'kcc';
    case AVW = 'avw';
    case RvA = 'rva';
    case SC = 'sc';

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
            self::Committee => $translator->translate('Commissie', locale: $language?->getLangParam()),
            self::AVC => $translator->translate('ALV-Commissie', locale: $language?->getLangParam()),
            self::Fraternity => $translator->translate('Dispuut', locale: $language?->getLangParam()),
            self::KCC => $translator->translate('KCC', locale: $language?->getLangParam()),
            self::AVW => $translator->translate('ALV-Werkgroep', locale: $language?->getLangParam()),
            self::RvA => $translator->translate('RvA', locale: $language?->getLangParam()),
            self::SC => $translator->translate('Stemcommissie', locale: $language?->getLangParam()),
        };

        return $translator->translate($function, locale: $language?->getLangParam());
    }

    public function hasOrganRegulations(): bool
    {
        return in_array($this, [self::Committee, self::Fraternity, self::KCC]);
    }

    /**
     * Returns a list of types (and its translations)
     *
     * @return array<string, string>
     */
    public static function getTypesArray(
        Translator $translator,
        ?AppLanguages $language = null,
    ): array {
        return array_combine(
            array_map(static function ($func) {
                return $func->value;
            }, self::cases()),
            array_map(static function ($func) use ($translator, $language) {
                return $func->getName($translator, $language);
            }, self::cases()),
        );
    }
}
