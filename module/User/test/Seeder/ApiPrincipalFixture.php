<?php

declare(strict_types=1);

namespace UserTest\Seeder;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use User\Model\ApiPrincipal;
use User\Model\Enums\ApiPermissions;

/**
 * A principal per interesting permission combination: some permissions gate an endpoint, some add or remove
 * individual member properties, and one decides whether deleted members are visible.
 *
 * Tokens stay randomly generated — {@see ApiPrincipal} deliberately offers no way to set one — so principals are
 * addressed by their stable description instead.
 */
class ApiPrincipalFixture extends AbstractFixture
{
    public const string DESCRIPTION_PREFIX = 'golden:';

    /**
     * Permissions that do not gate an endpoint but change the shape of the member objects it returns.
     */
    private const array MEMBER_MODIFIERS = [
        ApiPermissions::OrgansMembershipR,
        ApiPermissions::MembersPropertyKeyholder,
        ApiPermissions::MembersPropertyType,
        ApiPermissions::MembersPropertyEmail,
        ApiPermissions::MembersPropertyBirthDate,
        ApiPermissions::MembersPropertyAge16,
        ApiPermissions::MembersPropertyAge18,
        ApiPermissions::MembersPropertyAge21,
        ApiPermissions::MembersDeleted,
    ];

    public function load(ObjectManager $manager): void
    {
        $principals = [
            'none' => [],
            'all' => [ApiPermissions::All],
            'health' => [ApiPermissions::HealthR],
            'members' => [ApiPermissions::MembersR],
            'members-active' => [ApiPermissions::MembersActiveR],
            'organ-functions' => [ApiPermissions::OrganFunctionsListR],
            'board-functions' => [ApiPermissions::BoardFunctionsListR],
        ];

        // One per modifier in isolation, so a single property's visibility is not masked by the other eight.
        foreach (self::MEMBER_MODIFIERS as $modifier) {
            $principals['members-' . $modifier->value] = [ApiPermissions::MembersR, $modifier];
        }

        $principals['members-full'] = [ApiPermissions::MembersR, ...self::MEMBER_MODIFIERS];
        $principals['members-active-full'] = [ApiPermissions::MembersActiveR, ...self::MEMBER_MODIFIERS];

        foreach ($principals as $name => $permissions) {
            $principal = new ApiPrincipal();
            $principal->setDescription(self::DESCRIPTION_PREFIX . $name);
            $principal->setPermissions($permissions);
            $principal->generateToken();

            $manager->persist($principal);
        }

        $manager->flush();
    }
}
