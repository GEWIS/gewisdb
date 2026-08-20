<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\User\ApiPrincipal;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

use function assert;

/**
 * Security token of a request that authenticated with an API bearer token.
 *
 * Being a distinct type is what lets the voter and the exception listener tell an authenticated API request apart
 * from an authenticated session of the web interface.
 */
final class ApiToken extends PostAuthenticationToken
{
    public function __construct(
        ApiPrincipalUser $user,
        string $firewallName,
    ) {
        parent::__construct(
            $user,
            $firewallName,
            $user->getRoles(),
        );
    }

    public function getApiPrincipal(): ApiPrincipal
    {
        $user = $this->getUser();
        assert($user instanceof ApiPrincipalUser);

        return $user->getApiPrincipal();
    }
}
