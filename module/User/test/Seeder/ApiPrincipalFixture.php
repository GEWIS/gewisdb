<?php

declare(strict_types=1);

namespace UserTest\Seeder;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use User\Model\ApiPrincipal;
use User\Model\Enums\ApiPermissions;

/**
 * API principals covering the permission matrix that the API responses are shaped by.
 *
 * The API surface is a contract with other GEWIS systems, and the shape of a response depends on which permissions
 * the calling principal holds: some permissions gate the endpoint outright, some add or remove individual properties
 * of a member, and one decides whether deleted members are visible at all. Capturing that contract therefore needs a
 * principal per interesting combination, not just one "can do everything" token.
 *
 * Tokens are deliberately still generated randomly — {@see ApiPrincipal} intentionally offers no way to set one, and
 * that invariant is worth keeping. Consumers of this fixture (notably the golden capture in `scripts/goldens/`) look
 * the tokens up by description instead, which is why every principal here has a stable `golden:` prefixed one.
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
        // Bare principals: no permissions, everything, and one per endpoint gate. These pin down which endpoints each
        // gate actually opens, and what an under-permissioned request looks like.
        $principals = [
            'none' => [],
            'all' => [ApiPermissions::All],
            'health' => [ApiPermissions::HealthR],
            'members' => [ApiPermissions::MembersR],
            'members-active' => [ApiPermissions::MembersActiveR],
            'organ-functions' => [ApiPermissions::OrganFunctionsListR],
            'board-functions' => [ApiPermissions::BoardFunctionsListR],
        ];

        // One principal per modifier, so a change to a single property's visibility shows up in isolation rather than
        // being masked by the other eight.
        foreach (self::MEMBER_MODIFIERS as $modifier) {
            $principals['members-' . $modifier->value] = [ApiPermissions::MembersR, $modifier];
        }

        // And the fully-loaded variants, which are what a real consumer such as GEWISWEB looks like.
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
