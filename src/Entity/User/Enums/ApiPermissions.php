<?php

declare(strict_types=1);

namespace App\Entity\User\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Enum for keeping track of the claims that can be present in the JWT for ApiApps.
 */
enum ApiPermissions: string implements TranslatableInterface
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
    case BoardFunctionsListR = 'boards_functionslist_read';
    case All = '*';

    /**
     * The permission name, deferred so the caller decides on the locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::HealthR => new TranslatableMessage('Get API Health'),
            self::MembersR => new TranslatableMessage('Get all Members'),
            self::MembersActiveR => new TranslatableMessage(
                'Get active Members (members that are in one or more bodies)',
            ),
            self::MembersPropertyKeyholder => new TranslatableMessage('Member¹ - Check if keyholder'),
            self::MembersPropertyType => new TranslatableMessage('Member¹ - Check membership type'),
            self::MembersPropertyEmail => new TranslatableMessage('Member¹ - Get email address'),
            self::MembersPropertyBirthDate => new TranslatableMessage('Member¹ - Get birthdate'),
            self::MembersPropertyAge16 => new TranslatableMessage('Member¹ - Check if has reached age 16'),
            self::MembersPropertyAge18 => new TranslatableMessage('Member¹ - Check if has reached age 18'),
            self::MembersPropertyAge21 => new TranslatableMessage('Member¹ - Check if has reached age 21'),
            self::MembersDeleted => new TranslatableMessage('Member¹ - Allow operations on `deleted\' members'),
            self::OrgansMembershipR => new TranslatableMessage('Bodies - Read body membership (per user/body)'),
            self::OrganFunctionsListR => new TranslatableMessage('Bodies - List functions and translations'),
            self::BoardFunctionsListR => new TranslatableMessage('Boards - List functions and translations'),
            self::All => new TranslatableMessage('All API permissions'),
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

    public function getString(): string
    {
        return $this->value;
    }

    /**
     * @return array<string,string>
     */
    public static function toArray(TranslatorInterface $translator): array
    {
        $response = [];
        foreach (self::cases() as $case) {
            $response[$case->value] = $case->trans($translator);
        }

        return $response;
    }
}
