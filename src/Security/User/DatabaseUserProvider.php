<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use function is_subclass_of;
use function mb_strtolower;
use function sprintf;
use function strstr;

/**
 * Loads users from the `users` table by their login.
 *
 * @implements UserProviderInterface<User>
 */
final class DatabaseUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        #[Autowire(env: 'default::LDAP_BASEDN')]
        private readonly ?string $ldapBaseDn = null,
        #[Autowire(env: 'default::LDAP_DOMAIN')]
        private readonly ?string $ldapDomain = null,
    ) {
    }

    #[Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $login = $this->canonicalLogin($identifier);
        $user = $this->userRepository->findByLogin($login);

        if (null !== $user) {
            return $user;
        }

        if ($this->usesLdap()) {
            // An AD account has no row here until it has logged in once. Hand out an unsaved user so the bind can
            // still happen; LdapUserProvisioner stores it only after that bind succeeded, which keeps failed logins
            // from seeding the table.
            $user = new User();
            $user->setLogin($login);

            return $user;
        }

        $exception = new UserNotFoundException(sprintf('There is no user with login "%s".', $login));
        $exception->setUserIdentifier($login);

        throw $exception;
    }

    #[Override]
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Cannot refresh a "%s".', $user::class));
        }

        // Not canonicalised again: the identifier of a user that was authenticated already is its stored login. A
        // user that has been deleted in the meantime loses its session on its next request.
        $refreshedUser = $this->userRepository->findByLogin($user->getUserIdentifier());

        if (null === $refreshedUser) {
            $exception = new UserNotFoundException(sprintf('User "%s" no longer exists.', $user->getUserIdentifier()));
            $exception->setUserIdentifier($user->getUserIdentifier());

            throw $exception;
        }

        return $refreshedUser;
    }

    #[Override]
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of(
            $class,
            User::class,
        );
    }

    #[Override]
    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string $newHashedPassword,
    ): void {
        if (!$user instanceof User) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->userRepository->persist($user);
    }

    private function usesLdap(): bool
    {
        return null !== $this->ldapBaseDn && '' !== $this->ldapBaseDn;
    }

    /**
     * The login as it is (or would be) stored.
     *
     * Against AD the submitted account name is stored in principal form, which is also what makes a user count as
     * non-local. The domain always comes from configuration, never from what was typed.
     */
    private function canonicalLogin(string $identifier): string
    {
        if (!$this->usesLdap()) {
            return $identifier;
        }

        $account = strstr(
            $identifier,
            '@',
            true,
        );

        return mb_strtolower(false === $account ? $identifier : $account) . '@' . $this->ldapDomain;
    }
}
