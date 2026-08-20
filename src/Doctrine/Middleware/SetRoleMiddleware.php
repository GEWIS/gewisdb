<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Override;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

use function implode;

/**
 * Issues `SET ROLE` per connection so the application runs as a least-privileged role rather than the schema owner.
 *
 * Tagged without a `connection` attribute because it applies to both connections, which use different roles; the
 * right one is selected by host:port:dbname in {@see SetRoleDriver}.
 */
#[Autoconfigure(tags: ['doctrine.middleware'])]
class SetRoleMiddleware implements MiddlewareInterface
{
    #[Override]
    public function wrap(DriverInterface $driver): DriverInterface
    {
        $roleDefaultHost = $_ENV['DOCTRINE_DEFAULT_HOST'] ?? false;
        $roleDefaultPort = $_ENV['DOCTRINE_DEFAULT_PORT'] ?? false;
        $roleDefaultDB = $_ENV['DOCTRINE_DEFAULT_DATABASE'] ?? false;
        $roleDefaultRole = $_ENV['DOCTRINE_DEFAULT_ROLE'] ?? false;

        $roleReportHost = $_ENV['DOCTRINE_REPORT_HOST'] ?? false;
        $roleReportPort = $_ENV['DOCTRINE_REPORT_PORT'] ?? false;
        $roleReportDB = $_ENV['DOCTRINE_REPORT_DATABASE'] ?? false;
        $roleReportRole = $_ENV['DOCTRINE_REPORT_ROLE'] ?? false;

        if (
            false === $roleDefaultHost
            || false === $roleDefaultPort
            || false === $roleDefaultDB
            || false === $roleDefaultRole
        ) {
            throw new RuntimeException('Required `DOCTRINE_DEFAULT_*` environment variables not set...');
        }

        if (
            false === $roleReportHost
            || false === $roleReportPort
            || false === $roleReportDB
            || false === $roleReportRole
        ) {
            throw new RuntimeException('Required `DOCTRINE_REPORT_*` environment variables not set...');
        }

        $roles = [
            implode(
                ':',
                [
                    $roleDefaultHost,
                    $roleDefaultPort,
                    $roleDefaultDB,
                ],
            ) => $roleDefaultRole,
            implode(
                ':',
                [
                    $roleReportHost,
                    $roleReportPort,
                    $roleReportDB,
                ],
            ) => $roleReportRole,
        ];

        return new SetRoleDriver(
            $driver,
            $roles,
        );
    }
}
