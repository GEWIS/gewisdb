<?php

declare(strict_types=1);

/**
 * Minimal declarations of the Symfony Security contracts the entities implement, for tools that have to load those
 * entities before symfony/security-core is installed. Each is guarded, so this file is inert once it is.
 */

namespace Symfony\Component\Security\Core\User {
    if (!interface_exists(UserInterface::class)) {
        interface UserInterface
        {
            /** @return string[] */
            public function getRoles(): array;

            public function getUserIdentifier(): string;
        }
    }

    if (!interface_exists(PasswordAuthenticatedUserInterface::class)) {
        interface PasswordAuthenticatedUserInterface
        {
            public function getPassword(): ?string;
        }
    }
}
