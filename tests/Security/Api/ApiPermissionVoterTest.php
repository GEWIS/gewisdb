<?php

declare(strict_types=1);

namespace App\Tests\Security\Api;

use App\Entity\User\ApiPrincipal;
use App\Entity\User\Enums\ApiPermissions;
use App\Security\Api\ApiPermissionVoter;
use App\Security\Api\ApiPrincipalUser;
use App\Security\Api\ApiToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(ApiPermissionVoter::class)]
class ApiPermissionVoterTest extends TestCase
{
    public function testGrantsAPermissionThePrincipalHolds(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote(
                $this->tokenFor(ApiPermissions::MembersR),
                ApiPermissions::MembersR,
            ),
        );
    }

    public function testDeniesAPermissionThePrincipalDoesNotHold(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(
                $this->tokenFor(ApiPermissions::MembersR),
                ApiPermissions::MembersDeleted,
            ),
        );
    }

    /**
     * The wildcard lives in {@see ApiPrincipal::can()}; the voter is what proves it reaches an attribute check.
     */
    public function testGrantsEverythingToAPrincipalHoldingTheWildcard(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote(
                $this->tokenFor(ApiPermissions::All),
                ApiPermissions::MembersDeleted,
            ),
        );
    }

    public function testDeniesAPrincipalWithoutAnyPermissions(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(
                $this->tokenFor(),
                ApiPermissions::HealthR,
            ),
        );
    }

    /**
     * A session of the web interface carries no principal, so an API permission cannot be granted to it.
     */
    public function testDeniesATokenThatDidNotAuthenticateWithABearerToken(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(
                self::createStub(TokenInterface::class),
                ApiPermissions::MembersR,
            ),
        );
    }

    public function testAbstainsOnAttributesThatAreNotPermissions(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            new ApiPermissionVoter()->vote(
                $this->tokenFor(ApiPermissions::All),
                null,
                ['ROLE_ADMIN'],
            ),
        );
        self::assertFalse(new ApiPermissionVoter()->supportsAttribute('ROLE_ADMIN'));
        self::assertTrue(new ApiPermissionVoter()->supportsAttribute(ApiPermissions::MembersR->value));
    }

    private function vote(
        TokenInterface $token,
        ApiPermissions $attribute,
    ): int {
        return new ApiPermissionVoter()->vote(
            $token,
            null,
            [$attribute->value],
        );
    }

    private function tokenFor(ApiPermissions ...$permissions): ApiToken
    {
        $principal = new ApiPrincipal();
        $principal->setPermissions($permissions);

        return new ApiToken(
            new ApiPrincipalUser($principal),
            'api',
        );
    }
}
