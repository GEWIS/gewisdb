<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function in_array;

/**
 * Enum for the different organ types.
 */
enum OrganTypes: string implements TranslatableInterface
{
    case Committee = 'committee';
    case AVC = 'avc';
    case Fraternity = 'fraternity';
    case KCC = 'kcc';
    case AVW = 'avw';
    case RvA = 'rva';
    case SC = 'sc';

    /**
     * The organ type name, deferred so the caller decides on the locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Committee => new TranslatableMessage('Commissie'),
            self::AVC => new TranslatableMessage('ALV-Commissie'),
            self::Fraternity => new TranslatableMessage('Dispuut'),
            self::KCC => new TranslatableMessage('KCC'),
            self::AVW => new TranslatableMessage('ALV-Werkgroep'),
            self::RvA => new TranslatableMessage('RvA'),
            self::SC => new TranslatableMessage('Stemcommissie'),
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

    public function hasOrganRegulations(): bool
    {
        return in_array(
            $this,
            [
                self::Committee,
                self::Fraternity,
                self::KCC,
            ],
        );
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
}
