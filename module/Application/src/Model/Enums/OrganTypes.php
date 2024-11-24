<?php

declare(strict_types=1);

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\DummyTranslator;
use Laminas\Mvc\I18n\Translator;

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
        };

        return $translator->translate($function, locale: $language?->getLangParam());
    }
}
