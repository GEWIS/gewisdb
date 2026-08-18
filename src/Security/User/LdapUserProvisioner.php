<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Gives an account that authenticated against AD its row in `users`.
 *
 * The row exists so the rest of the application has a real user to point at. It is written here, after the bind has
 * succeeded, rather than while the user is being loaded, so that a wrong password cannot create accounts.
 */
#[AsEventListener(event: LoginSuccessEvent::class)]
final readonly class LdapUserProvisioner
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (
            !$user instanceof User
            || null !== $user->getId()
        ) {
            return;
        }

        $this->userRepository->persist($user);
    }
}
