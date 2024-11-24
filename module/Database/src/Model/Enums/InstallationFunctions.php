<?php

declare(strict_types=1);

namespace Database\Model\Enums;

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

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Chair => $translator->translate('Chair'),
            self::Secretary => $translator->translate('Secretary'),
            self::Treasurer => $translator->translate('Treasurer'),
            self::ViceChair => $translator->translate('Vice-Chair'),
            self::Opperhoofd => $translator->translate('Opperhoofd'),
            self::PrOfficer => $translator->translate('PR Officer'),
            self::FoosballCoordinator => $translator->translate('Foosball Coordinator'),
            self::Member => $translator->translate('Member'),
            self::InactiveMember => $translator->translate('Inactive Member'),
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
