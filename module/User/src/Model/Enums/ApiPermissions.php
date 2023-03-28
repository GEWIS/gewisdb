<?php

namespace User\Model\Enums;

use Decision\Model\Member as MemberModel;
use Laminas\Mvc\I18n\Translator;

/**
 * Enum for keeping track of the claims that can be present in the JWT for ApiApps.
 */
enum ApiPermissions: string
{
    case HealthR = 'health_read';
    case MembersR = 'members_read';
    case All = '*';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::HealthR => $translator->translate('Get API Health'),
            self::MembersR => $translator->translate('Get all Members'),
            self::All => $translator->translate('All API permissions'),
        };
    }

    public function getString(): string
    {
        return $this->value;
    }
}
