<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use App\Entity\Application\Enums\AppLanguages;
use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_combine;
use function array_filter;
use function array_map;
use function array_values;
use function in_array;

/**
 * Enum with organ functions
 * The values are in Dutch, because decisions are made in Dutch and thus this value is guaranteed to not change
 */
enum InstallationFunctions: string implements TranslatableInterface
{
    /** Current functions */
    case Chair = 'Voorzitter';
    case Secretary = 'Secretaris';
    case Treasurer = 'Penningmeester';
    case ViceChair = 'Vice-Voorzitter';
    case Opperhoofd = 'Opperhoofd';
    case PrOfficer = 'PR-Functionaris';

    /** Legacy functions */
    case FoosballCoordinator = 'Tafelvoetbalcoordinator';
    case ProcurementOfficer = 'Inkoper';

    /** Administrative functions */
    case Member = 'Lid';
    case InactiveMember = 'Inactief Lid';

    public function isLegacy(): bool
    {
        return in_array(
            $this,
            [
                self::FoosballCoordinator,
                self::ProcurementOfficer,
            ],
        );
    }

    public function isAdministrative(): bool
    {
        return in_array(
            $this,
            [
                self::Member,
                self::InactiveMember,
            ],
        );
    }

    /**
     * The functions that can be picked when installing someone.
     *
     * @return self[]
     */
    public static function currentCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $case): bool => !$case->isLegacy() && !$case->isAdministrative(),
        ));
    }

    /**
     * The functions that were used in the past but can no longer be picked.
     *
     * @return self[]
     */
    public static function legacyCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $case): bool => $case->isLegacy(),
        ));
    }

    /**
     * The functions the database assigns itself.
     *
     * @return self[]
     */
    public static function administrativeCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $case): bool => $case->isAdministrative(),
        ));
    }

    /**
     * The function name, deferred so the caller decides on the locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Chair => new TranslatableMessage('Voorzitter'),
            self::Secretary => new TranslatableMessage('Secretaris'),
            self::Treasurer => new TranslatableMessage('Penningmeester'),
            self::ViceChair => new TranslatableMessage('Vice-Voorzitter'),
            self::Opperhoofd => new TranslatableMessage('Opperhoofd'),
            self::PrOfficer => new TranslatableMessage('PR-Functionaris'),
            self::FoosballCoordinator => new TranslatableMessage('Tafelvoetbalcoordinator'),
            self::ProcurementOfficer => new TranslatableMessage('Inkoper'),
            self::Member => new TranslatableMessage('Lid'),
            self::InactiveMember => new TranslatableMessage('Inactief Lid'),
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->getName()->trans(
            $translator,
            $locale,
        );
    }

    /**
     * Returns a list of functions (and its translations)
     *
     * @return array<non-empty-string, array{
     *  isAdministrative: bool,
     *  isLegacy: bool,
     *  translations: non-empty-array<array-key, string>
     * }>
     */
    public static function getMultilangArray(
        TranslatorInterface $translator,
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
            array_map(
                static function ($func) {
                    return $func->value;
                },
                $cases,
            ),
            array_map(
                static function ($func) use ($translator) {
                    return [
                        'translations' => [
                            AppLanguages::English->getLangParam() => $func->trans(
                                $translator,
                                AppLanguages::English->getLangParam(),
                            ),
                            AppLanguages::Dutch->getLangParam() => $func->trans(
                                $translator,
                                AppLanguages::Dutch->getLangParam(),
                            ),
                        ],
                        'isAdministrative' => $func->isAdministrative(),
                        'isLegacy' => $func->isLegacy(),
                    ];
                },
                $cases,
            ),
        );
    }
}
