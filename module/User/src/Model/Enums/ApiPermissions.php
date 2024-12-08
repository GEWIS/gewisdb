<?php

declare(strict_types=1);

namespace User\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for keeping track of the claims that can be present in the JWT for ApiApps.
 */
enum ApiPermissions: string
{
    case HealthR = 'health_read';
    case MembersR = 'members_read';
    case MembersActiveR = 'members_active_read';
    case MembersPropertyKeyholder = 'members_read_keyholder';
    case MembersPropertyType = 'members_read_type';
    case MembersPropertyEmail = 'members_read_email';
    case MembersPropertyBirthDate = 'members_read_birthdate';
    case MembersPropertyAge16 = 'members_read_is16';
    case MembersPropertyAge18 = 'members_read_is18';
    case MembersPropertyAge21 = 'members_read_is21';
    case MembersDeleted = 'members_deleted';
    case OrgansMembershipR = 'organs_members_read';
    case OrganFunctionsListR = 'organs_functionslist_read';
    case All = '*';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::HealthR => $translator->translate('Get API Health'),
            self::MembersR => $translator->translate('Get all Members'),
            self::MembersActiveR => $translator->translate(
                'Get active Members (members that are in one or more organs)',
            ),
            self::MembersPropertyKeyholder => $translator->translate(
                'Member¹ - Check if keyholder',
            ),
            self::MembersPropertyType => $translator->translate(
                'Member¹ - Check membership type',
            ),
            self::MembersPropertyEmail => $translator->translate('Member¹ - Get email address'),
            self::MembersPropertyBirthDate => $translator->translate('Member¹ - Get birthdate'),
            self::MembersPropertyAge16 => $translator->translate('Member¹ - Check if has reached age 16'),
            self::MembersPropertyAge18 => $translator->translate('Member¹ - Check if has reached age 18'),
            self::MembersPropertyAge21 => $translator->translate('Member¹ - Check if has reached age 21'),
            self::MembersDeleted => $translator->translate('Member¹ - Allow operations on `deleted\' members'),
            self::OrgansMembershipR => $translator->translate('Organs - Read organ membership (per user/organ)'),
            self::OrganFunctionsListR => $translator->translate('Organs - List functions and translations'),
            self::All => $translator->translate('All API permissions'),
        };
    }

    public function getString(): string
    {
        return $this->value;
    }

    /**
     * @return array<string,string>
     */
    public static function toArray(Translator $translator): array
    {
        $response = [];
        foreach (self::cases() as $case) {
            $response[$case->value] = $case->getName($translator);
        }

        return $response;
    }
}
