<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\User\ApiPrincipal;
use Override;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Security adapter around an {@see ApiPrincipal}.
 *
 * The entity is deliberately left free of security interfaces: its natural identifier is the bearer token itself,
 * which must never end up in logs, profiler panels or exception messages as a user identifier. The adapter answers
 * with an opaque identifier instead, while the principal keeps its single responsibility.
 */
final class ApiPrincipalUser implements UserInterface
{
    public const string ROLE = 'ROLE_API';

    public function __construct(private readonly ApiPrincipal $principal)
    {
    }

    public function getApiPrincipal(): ApiPrincipal
    {
        return $this->principal;
    }

    /**
     * @return string[]
     */
    #[Override]
    public function getRoles(): array
    {
        return [self::ROLE];
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        return 'api-principal-' . $this->principal->getId();
    }

    /**
     * There are no credentials to hold on to; the bearer token is looked up on every request.
     *
     * Intentionally without #[Override]: `UserInterface::eraseCredentials()` is deprecated as of Symfony 7.3 and
     * disappears with Symfony 8, at which point the attribute would turn this into a fatal error.
     */
    public function eraseCredentials(): void
    {
    }
}
