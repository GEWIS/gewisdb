<?php

declare(strict_types=1);

namespace App\Entity\Decision\Enums;

use App\Entity\Application\Enums\AppLanguages;
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
     * Whether this type of organ must be led by a chair.
     *
     * Per the Articles of Association ("Iedere commissie heeft een voorzitter", art. 22.3) and the Internal
     * Regulations (art. 11.3.1, 13, and 16) every organ has a chair among its members.
     */
    public function requiresChair(): bool
    {
        return true;
    }

    /**
     * Whether this type of organ knows inactive members ("Inactief Lid").
     *
     * Only fraternities do: they keep members who no longer study (HR art. 13), who then hold no function. Every other
     * organ simply discharges whoever is no longer part of it.
     */
    public function allowsInactiveMembers(): bool
    {
        return self::Fraternity === $this;
    }

    /**
     * The number of active members this type of organ must have at all times.
     *
     * A fraternity has "tenminste 3 actieve dispuutsleden" (HR art. 13.8) and a GMM taskforce "tenminste 3 leden"
     * (HR art. 16.5); both counts include the chair. Every other organ needs at least someone in it, if only the
     * chair it is required to have. The Internal Regulations and the Articles of Association put a higher floor under
     * the KCC, the AVC, the RvA, and the voting committee as well; those are not encoded here yet.
     *
     * Members installed as "Inactief Lid" do not count towards this, they hold no function in the organ.
     */
    public function getMinimumMembers(): int
    {
        return match ($this) {
            self::Fraternity,
            self::AVW => 3,
            default => 1,
        };
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
