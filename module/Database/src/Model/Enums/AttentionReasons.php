<?php

declare(strict_types=1);

namespace Database\Model\Enums;

use Application\Model\Enums\AppLanguages;
use Laminas\Mvc\I18n\DummyTranslator;
use Laminas\Mvc\I18n\Translator;

use function sprintf;
use function ucfirst;

/**
 * Enum with board functions
 * The values are in Dutch, because decisions are made in Dutch and thus this value is guaranteed to not change
 */
enum AttentionReasons: string
{
    /** Member */
    case MissingEmail = 'missing_email';
    case MissingStudentIdOrdinary = 'missing_student_id_ordinary';
    case ExpiringExternalActive = 'expiring_external_active';
    case ExpiringExternalNonActive = 'expiring_external_non_active';
    case ExpiringOrdinaryActive = 'expiring_ordinary_active';
    case ExpiringOrdinaryNonActive = 'expiring_ordinary_non_active';
    case ExpiringGraduateActiveInactive = 'expiring_graduate_active_inactive';

    /**
     * Give the function name with the given translation. If no translator is given, we return the default language.
     */
    public function getLabel(
        ?Translator $translator,
        ?AppLanguages $language = null,
    ): string {
        if (null === $translator) {
            $translator = new DummyTranslator();
        }

        return match ($this) {
            self::MissingEmail => $translator->translate(
                'No email address',
                locale: $language?->getLangParam(),
            ),
            self::MissingStudentIdOrdinary => $translator->translate(
                'Ordinary member without student ID',
                locale: $language?->getLangParam(),
            ),
            self::ExpiringExternalActive => sprintf(
                $translator->translate(
                    '%s %s member expiring soon',
                    locale: $language?->getLangParam(),
                ),
                ucfirst($translator->translate('external', locale: $language?->getLangParam())),
                $translator->translate('active', locale: $language?->getLangParam()),
            ),
            self::ExpiringExternalNonActive => sprintf(
                $translator->translate(
                    '%s %s member expiring soon',
                    locale: $language?->getLangParam(),
                ),
                ucfirst($translator->translate('external', locale: $language?->getLangParam())),
                $translator->translate('non-active', locale: $language?->getLangParam()),
            ),
            self::ExpiringOrdinaryActive => sprintf(
                $translator->translate(
                    '%s %s member expiring soon',
                    locale: $language?->getLangParam(),
                ),
                ucfirst($translator->translate('ordinary', locale: $language?->getLangParam())),
                $translator->translate('active', locale: $language?->getLangParam()),
            ),
            self::ExpiringOrdinaryNonActive => sprintf(
                $translator->translate(
                    '%s %s member expiring soon',
                    locale: $language?->getLangParam(),
                ),
                ucfirst($translator->translate('ordinary', locale: $language?->getLangParam())),
                $translator->translate('non-active', locale: $language?->getLangParam()),
            ),
            self::ExpiringGraduateActiveInactive => sprintf(
                $translator->translate(
                    '%s %s member expiring soon',
                    locale: $language?->getLangParam(),
                ),
                ucfirst($translator->translate('graduate', locale: $language?->getLangParam())),
                $translator->translate('active/inactive', locale: $language?->getLangParam()),
            ),
            default => $translator->translate('Unknown reason', locale: $language?->getLangParam()),
        };
    }

    /**
     * Get the recommended action for this attention reason.
     */
    public function getRecommendedAction(
        ?Translator $translator,
        ?AppLanguages $language = null,
    ): string {
        if (null === $translator) {
            $translator = new DummyTranslator();
        }

        return match ($this) {
            self::MissingEmail => ucfirst(
                $translator->translate(
                    'complete missing profile information',
                    locale: $language?->getLangParam(),
                ),
            ),
            self::MissingStudentIdOrdinary => ucfirst(
                sprintf(
                    $translator->translate(
                        '%s OR %s',
                        locale: $language?->getLangParam(),
                    ),
                    $translator->translate(
                        'complete missing profile information',
                        locale: $language?->getLangParam(),
                    ),
                    $translator->translate(
                        'renew membership as external',
                        locale: $language?->getLangParam(),
                    ),
                ),
            ),
            self::ExpiringExternalActive => ucfirst(
                $translator->translate(
                    'renew membership as external',
                    locale: $language?->getLangParam(),
                ),
            ),
            self::ExpiringExternalNonActive => ucfirst(
                $translator->translate(
                    'proof of (non-TU/e) study OR board decision => renew',
                    locale: $language?->getLangParam(),
                ),
            ),
            self::ExpiringOrdinaryActive => ucfirst(
                sprintf(
                    $translator->translate(
                        '%s OR %s',
                        locale: $language?->getLangParam(),
                    ),
                    $translator->translate(
                        'discharge from organ(s)',
                        locale: $language?->getLangParam(),
                    ),
                    $translator->translate(
                        'renew in appropriate type (ordinary or external)',
                        locale: $language?->getLangParam(),
                    ),
                ),
            ),
            self::ExpiringOrdinaryNonActive => ucfirst(
                sprintf(
                    $translator->translate(
                        '%s OR %s',
                        locale: $language?->getLangParam(),
                    ),
                    $translator->translate(
                        '(bulk) renew as ordinary',
                        locale: $language?->getLangParam(),
                    ),
                    $translator->translate(
                        'convert to graduate',
                        locale: $language?->getLangParam(),
                    ),
                ),
            ),
            self::ExpiringGraduateActiveInactive => ucfirst(
                sprintf(
                    $translator->translate(
                        '%s OR %s',
                        locale: $language?->getLangParam(),
                    ),
                    $translator->translate(
                        'contact to renew graduate status',
                        locale: $language?->getLangParam(),
                    ),
                    $translator->translate(
                        'discharge from organ(s)',
                        locale: $language?->getLangParam(),
                    ),
                ),
            ),
            default => $translator->translate('No action recommended', locale: $language?->getLangParam()),
        };
    }

    public function renewRecommended(): bool
    {
        return match ($this) {
            self::MissingStudentIdOrdinary,
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
            self::MissingStudentIdOrdinary => true,
            default => false,
        };
    }
}
