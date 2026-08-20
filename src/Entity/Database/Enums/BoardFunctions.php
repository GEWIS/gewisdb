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
use function in_array;

/**
 * Enum with board functions
 * The values are in Dutch, because decisions are made in Dutch and thus this value is guaranteed to not change
 */
enum BoardFunctions: string implements TranslatableInterface
{
    /** Current functions */
    case Chair = 'Voorzitter';
    case Secretary = 'Secretaris';
    case Treasurer = 'Penningmeester';
    case Education = 'Commissaris Onderwijs';
    case ExternalAffairs = 'Commissaris Externe Betrekkingen';
    case InternalAffairs = 'Commissaris Interne Betrekkingen';

    /** Legacy functions */
    case LegacyEducation = 'Onderwijscommissaris';
    case PrOfficer = 'PR-Functionaris';
    case ViceChair = 'Vice-Voorzitter';

    /** One-off functions */
    case BrandManager = 'Brand Manager';
    case CareerdevelopmentExternalAffairs = 'Commissaris Carrièreontwikkeling en Externe Betrekkingen';
    case DigitalInfrastructure = 'Commissaris Digitale Infrastructuur';
    case Information = 'Commissaris Kennisbeheer';
    case Innovation = 'Commissaris Innovatie';
    case Community = 'Commissaris Verenigingsontwikkeling';
    case DigitalInnovation = 'Commissaris Digitale Innovatie';

    public function isLegacy(): bool
    {
        return !in_array(
            $this,
            [
                self::Chair,
                self::Secretary,
                self::Treasurer,
                self::Education,
                self::ExternalAffairs,
                self::InternalAffairs,
                self::DigitalInnovation,
            ],
        );
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
            self::Education => new TranslatableMessage('Commissaris Onderwijs'),
            self::ExternalAffairs => new TranslatableMessage('Commissaris Externe Betrekkingen'),
            self::InternalAffairs => new TranslatableMessage('Commissaris Interne Betrekkingen'),
            self::LegacyEducation => new TranslatableMessage('LEGACY Onderwijscommissaris'),
            self::PrOfficer => new TranslatableMessage('PR-Functionaris'),
            self::ViceChair => new TranslatableMessage('Vice-Voorzitter'),
            self::BrandManager => new TranslatableMessage('Brand Manager'),
            self::CareerdevelopmentExternalAffairs => new TranslatableMessage(
                'Commissaris Carrièreontwikkeling en Externe Betrekkingen',
            ),
            self::DigitalInfrastructure => new TranslatableMessage('Commissaris Digitale Infrastructuur'),
            self::Information => new TranslatableMessage('Commissaris Kennisbeheer'),
            self::Innovation => new TranslatableMessage('Commissaris Innovatie'),
            self::Community => new TranslatableMessage('Commissaris Verenigingsontwikkeling'),
            self::DigitalInnovation => new TranslatableMessage('Commissaris Digitale Innovatie'),
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
     *  isLegacy: bool,
     *  translations: non-empty-array<array-key, string>
     * }>
     */
    public static function getMultilangArray(
        TranslatorInterface $translator,
        bool $includeLegacy = true,
        bool $includeCurrent = true,
    ): array {
        $cases = array_filter(
            self::cases(),
            static function ($case) use ($includeLegacy, $includeCurrent) {
                return (!$case->isLegacy() || $includeLegacy) &&
                    ($case->isLegacy() || $includeCurrent);
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
                        'isLegacy' => $func->isLegacy(),
                    ];
                },
                $cases,
            ),
        );
    }
}
