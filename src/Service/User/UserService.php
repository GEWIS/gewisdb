<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        #[Autowire(env: 'default::LDAP_BASEDN')]
        private ?string $ldapBaseDn = null,
    ) {
    }

    /**
     * Whether accounts sign in against AD rather than against the local `password` column.
     *
     * The same base DN that decides this for the firewall decides it here, so an account created on this screen is
     * only usable for logging in while it is empty.
     */
    public function usesLdap(): bool
    {
        return null !== $this->ldapBaseDn && '' !== $this->ldapBaseDn;
    }

    /**
     * @return User[]
     */
    public function findAll(): array
    {
        return $this->userRepository->findAll();
    }

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(
        User $user,
        #[SensitiveParameter]
        string $plainPassword,
    ): void {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->userRepository->persist($user);
    }

    /**
     * An account that signs in through an external directory has no password stored here, so there is nothing to
     * hash for it.
     */
    public function changePassword(
        User $user,
        #[SensitiveParameter]
        ?string $plainPassword,
    ): void {
        if (
            $user->isLocal()
            && null !== $plainPassword
        ) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }

        $this->userRepository->persist($user);
    }

    public function remove(User $user): void
    {
        $this->userRepository->remove($user);
    }
}
