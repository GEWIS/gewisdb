<?php

declare(strict_types=1);

namespace App\Security\User;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Ldap\Security\LdapBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Decides, per request, whether the login form authenticates against AD or against the local `password` column.
 *
 * The Laminas application made that choice at runtime on the LDAP base DN, and it has to stay a runtime choice: an
 * empty base DN is not a legacy branch but how development and every deployment without AD logs in. Firewalls are
 * compiled, so the choice cannot be expressed in security.yaml; marking the LDAP badge resolved is what expresses it.
 * Symfony's own CheckLdapCredentialsListener then skips the bind, leaving the password credentials for
 * CheckCredentialsListener to verify against the stored hash.
 */
#[AsEventListener(event: CheckPassportEvent::class, priority: 512)]
final readonly class LocalPasswordFallbackListener
{
    public function __construct(
        #[Autowire(env: 'default::LDAP_BASEDN')]
        private ?string $ldapBaseDn = null,
    ) {
    }

    public function __invoke(CheckPassportEvent $event): void
    {
        if (null !== $this->ldapBaseDn && '' !== $this->ldapBaseDn) {
            return;
        }

        $passport = $event->getPassport();

        if (!$passport->hasBadge(LdapBadge::class)) {
            return;
        }

        /** @var LdapBadge $badge */
        $badge = $passport->getBadge(LdapBadge::class);
        $badge->markResolved();
    }
}
