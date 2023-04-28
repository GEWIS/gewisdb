<?php

declare(strict_types=1);

namespace User\Model\Enums;

use Laminas\I18n\Translator\TranslatorInterface;

/**
 * Enum for keeping track of the claims that can be present in the JWT for ApiApps.
 */
enum ApiPermissions: string
{
    case HealthR = 'health_read';
    case MembersR = 'members_read';
    case MembersActiveR = 'members_active_read';
    case OrgansMembershipR = 'organs_members_read';
    case All = '*';

    public function getName(TranslatorInterface $translator): string
    {
        return match ($this) {
            self::HealthR => $translator->translate('Get API Health'),
            self::MembersR => $translator->translate('Get all Members'),
            self::MembersActiveR => $translator->translate(
                'Get active Members (members that are in one or more organs)',
            ),
            self::OrgansMembershipR => $translator->translate('Read organ membership (per user/organ)'),
            self::All => $translator->translate('All API permissions'),
        };
    }

    public function getString(): string
    {
        return $this->value;
    }
}
