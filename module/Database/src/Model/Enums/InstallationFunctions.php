<?php

declare(strict_types=1);

namespace Database\Model\Enums;

use Application\Model\Enums\AppLanguages;
use Laminas\Mvc\I18n\DummyTranslator;
use Laminas\Mvc\I18n\Translator;

use function array_combine;
use function array_filter;
use function array_map;
use function in_array;

/**
 * Enum with organ and board functions
 * The values are in Dutch, because decisions are made in Dutch and thus this value is guaranteed to not change
 */
enum InstallationFunctions: string
{
    /** Current functions */
    case Chair = 'Voorzitter';
    case Secretary = 'Secretaris';
    case Treasurer = 'Penningmeester';
    case ViceChair = 'Vice-Voorzitter';
    case Opperhoofd = 'Opperhoofd';
    case PrOfficer = 'PR-functionaris';

    /** Legacy functions */
    case FoosballCoordinator = 'Tafelvoetbalcoordinator';

    /** Administrative functions */
    case Member = 'Lid';
    case InactiveMember = 'Inactief Lid';

    public function isLegacy(): bool
    {
        return in_array($this, [self::FoosballCoordinator]);
    }

    public function isAdministrative(): bool
    {
        return in_array($this, [self::Member, self::InactiveMember]);
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

        return match ($this) {
            self::Chair => $translator->translate('Chair', locale: $language?->getLangParam()),
            self::Secretary => $translator->translate('Secretary', locale: $language?->getLangParam()),
            self::Treasurer => $translator->translate('Treasurer', locale: $language?->getLangParam()),
            self::ViceChair => $translator->translate('Vice-Chair', locale: $language?->getLangParam()),
            self::Opperhoofd => $translator->translate('Opperhoofd', locale: $language?->getLangParam()),
            self::PrOfficer => $translator->translate('PR Officer', locale: $language?->getLangParam()),
            self::FoosballCoordinator => $translator->translate(
                'Foosball Coordinator',
                locale: $language?->getLangParam(),
            ),
            self::Member => $translator->translate('Member', locale: $language?->getLangParam()),
            self::InactiveMember => $translator->translate('Inactive Member', locale: $language?->getLangParam()),
        };
    }

    /**
     * Returns a list of functions (and its translations)
     *
     * @return array<string, string>
     */
    public static function getFunctionsArray(
        Translator $translator,
        bool $includeAdministrative = true,
        bool $includeLegacy = true,
        bool $includeCurrent = true,
    ): array {
        $cases = array_filter(
            self::cases(),
            static function ($case) use ($includeAdministrative, $includeLegacy, $includeCurrent) {
                return (!$case->isLegacy() || $includeLegacy) &&
                    (!$case->isAdministrative() || $includeAdministrative) &&
                    ($case->isAdministrative() || $case->isLegacy() || $includeCurrent);
            },
        );

        return array_combine(
            array_map(static function ($func) {
                return $func->value;
            }, $cases),
            array_map(static function ($func) use ($translator) {
                return $func->getName($translator);
            }, $cases),
        );
    }
}
