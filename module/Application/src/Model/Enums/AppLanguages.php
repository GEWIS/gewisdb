<?php

declare(strict_types=1);

namespace Application\Model\Enums;

use function explode;

/**
 * This enum contains the different translations that are supported by GEWISDB.
 *
 * LangParam is the format compatible with $translator->translate(locale: $langparam)
 * Locale should be compatible with dates
 *
 * @psalm-type LangParam = 'en'|'nl'
 * @psalm-type Locale = 'en_GB'|'nl_NL'
 */
enum AppLanguages: string
{
    case English = 'english_greatbritain';
    case Dutch = 'dutch_netherlands';

    /**
     * Get the language param ('en', 'nl') from a language
     * An explode is not possible because of psalm
     *
     * @return LangParam
     */
    public function getLangParam(): string
    {
        return match ($this) {
            self::English => 'en',
            self::Dutch => 'nl',
        };
    }

    /**
     * Get the locale ('en_GB', 'nl_NL') from a language
     *
     * @return Locale
     */
    public function getLocale(): string
    {
        return match ($this) {
            self::English => 'en_GB',
            self::Dutch => 'nl_NL',
        };
    }

    /**
     * Get the language from a language param ('en', 'nl')
     *
     * @param LangParam $langParam
     */
    public static function fromLangParam(string $langParam): AppLanguages
    {
        return match ($langParam) {
            'en' => self::English,
            'nl' => self::Dutch,
        };
    }

    /**
     * Get the language from a locale ('en_GB', 'nl_NL')
     *
     * @param Locale $locale
     */
    public static function fromLocale(string $locale): AppLanguages
    {
        return self::fromLangParam(explode('_', $locale)[0]);
    }
}
