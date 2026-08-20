<?php

declare(strict_types=1);

namespace App\Security\User;

use Closure;
use Override;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

use function array_filter;
use function array_map;
use function assert;
use function explode;
use function filter_var;
use function implode;
use function is_string;
use function sprintf;
use function str_replace;
use function trim;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOL;

/**
 * Reshapes the LDAP environment — a printf-style account filter, a comma-separated host list, a boolean for TLS —
 * into what ext_ldap and the login firewall expect. Doing it here rather than asking for new values keeps existing
 * deployments' environment working: every one of these translations would otherwise fail silently, as an unusable
 * filter or an unreachable server.
 */
final class LdapEnvVarProcessor implements EnvVarProcessorInterface
{
    private const int LDAP_PORT = 389;

    #[Override]
    public function getEnv(
        string $prefix,
        string $name,
        Closure $getEnv,
    ): string {
        $value = $getEnv($name);
        assert(is_string($value) || null === $value);
        $value = trim($value ?? '');

        return match ($prefix) {
            // The account filter is written in printf form, `(sAMAccountName=%s)`; Symfony substitutes
            // `{user_identifier}`.
            'ldap_filter' => str_replace(
                '%s',
                '{user_identifier}',
                $value,
            ),
            // A comma-separated host list becomes the space-separated URI list OpenLDAP accepts.
            'ldap_connection_string' => $this->connectionString($value),
            // STARTTLS on the default port is `tls`; `ssl` would be LDAPS on 636, which this never used.
            'ldap_encryption' => true === filter_var(
                $value,
                FILTER_VALIDATE_BOOL,
                FILTER_NULL_ON_FAILURE,
            )
                ? 'tls'
                : 'none',
            default => throw new RuntimeException(sprintf('Unsupported env var prefix "%s".', $prefix)),
        };
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public static function getProvidedTypes(): array
    {
        return [
            'ldap_filter' => 'string',
            'ldap_connection_string' => 'string',
            'ldap_encryption' => 'string',
        ];
    }

    private function connectionString(string $servers): string
    {
        $hosts = array_filter(
            array_map(
                trim(...),
                explode(
                    ',',
                    $servers,
                ),
            ),
            static fn (string $host): bool => '' !== $host,
        );

        return implode(
            ' ',
            array_map(
                static fn (string $host): string => 'ldap://' . $host . ':' . self::LDAP_PORT,
                $hosts,
            ),
        );
    }
}
