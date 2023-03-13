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

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::HealthR => $translator->translate('Get API Health'),
        };
    }
}
