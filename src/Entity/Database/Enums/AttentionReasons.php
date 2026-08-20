<?php

declare(strict_types=1);

namespace App\Entity\Database\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Enum describing why a member/membership requires attention in the overview.
 *
 * A reason carries two pieces of prose: why the member surfaced (`getLabel()`) and what to do about it
 * (`getRecommendedAction()`). `trans()` renders the former, since that is the reason's name.
 */
enum AttentionReasons: string implements TranslatableInterface
{
    /** Member */
    case MissingEmail = 'missing_email';
    case MissingStudentNumberOrdinary = 'missing_student_number_ordinary';
    case ExpiringExternalActive = 'expiring_external_active';
    case ExpiringExternalNonActive = 'expiring_external_non_active';
    case ExpiringOrdinaryActive = 'expiring_ordinary_active';
    case ExpiringOrdinaryNonActive = 'expiring_ordinary_non_active';
    case ExpiringGraduateActiveInactive = 'expiring_graduate_active_inactive';

    /**
     * Why the member surfaced, deferred so the caller decides on the locale (or takes the source string).
     *
     * The expiry reasons are one sentence with two named placeholders. `%type%` is the membership type itself,
     * so the two stay in step, and both parameters are nested translatables that resolve in whichever locale the
     * sentence is finally rendered in.
     */
    public function getLabel(): TranslatableMessage
    {
        return match ($this) {
            self::MissingEmail => new TranslatableMessage('No email address'),
            self::MissingStudentNumberOrdinary => new TranslatableMessage('Ordinary member without student number'),
            self::ExpiringExternalActive => self::expiring(
                MembershipTypes::External,
                'active',
            ),
            self::ExpiringExternalNonActive => self::expiring(
                MembershipTypes::External,
                'non-active',
            ),
            self::ExpiringOrdinaryActive => self::expiring(
                MembershipTypes::Ordinary,
                'active',
            ),
            self::ExpiringOrdinaryNonActive => self::expiring(
                MembershipTypes::Ordinary,
                'non-active',
            ),
            self::ExpiringGraduateActiveInactive => self::expiring(
                MembershipTypes::Graduate,
                'active/inactive',
            ),
            default => new TranslatableMessage('Unknown reason'),
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->getLabel()->trans(
            $translator,
            $locale,
        );
    }

    /**
     * Get the recommended action for this attention reason.
     *
     * Each action is a single catalogue entry rather than fragments joined by a translated conjunction: the joined
     * form put the capitalisation of the first fragment outside the catalogue, where no translator could reach it.
     */
    public function getRecommendedAction(): TranslatableMessage
    {
        return match ($this) {
            self::MissingEmail => new TranslatableMessage('Complete missing profile information'),
            self::MissingStudentNumberOrdinary =>
                new TranslatableMessage('Complete missing profile information OR renew membership as external'),
            self::ExpiringExternalActive => new TranslatableMessage('Renew membership as external'),
            self::ExpiringExternalNonActive => new TranslatableMessage(
                'Proof of (non-TU/e) study OR board decision => renew',
            ),
            self::ExpiringOrdinaryActive =>
                new TranslatableMessage('Discharge from bodies OR renew in appropriate type (ordinary or external)'),
            self::ExpiringOrdinaryNonActive => new TranslatableMessage(
                '(bulk) renew as ordinary OR convert to graduate',
            ),
            self::ExpiringGraduateActiveInactive => new TranslatableMessage(
                'Contact to renew graduate status OR discharge from bodies',
            ),
            default => new TranslatableMessage('No action recommended'),
        };
    }

    public function renewRecommended(): bool
    {
        return match ($this) {
            self::MissingStudentNumberOrdinary,
            self::ExpiringExternalActive,
            self::ExpiringExternalNonActive,
            self::ExpiringOrdinaryActive,
            self::ExpiringOrdinaryNonActive => true,
            default => false,
        };
    }

    public function editRecommended(): bool
    {
        return match ($this) {
            self::MissingEmail,
            self::MissingStudentNumberOrdinary => true,
            default => false,
        };
    }

    public function includeBulkGraduateConversion(): bool
    {
        return match ($this) {
            self::ExpiringOrdinaryNonActive,
            self::ExpiringExternalNonActive => true,
            default => false,
        };
    }

    public function includeBulkActiveMemberRenewal(): bool
    {
        return match ($this) {
            self::ExpiringOrdinaryActive,
            self::ExpiringExternalActive => true,
            default => false,
        };
    }

    private static function expiring(
        MembershipTypes $type,
        string $membership,
    ): TranslatableMessage {
        return new TranslatableMessage(
            '%type% %membership% member expiring soon',
            [
                '%type%' => $type,
                '%membership%' => new TranslatableMessage($membership),
            ],
        );
    }
}
