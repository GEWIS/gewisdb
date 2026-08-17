<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Override;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

use function implode;

/**
 * Issues `SET ROLE` on every new PostgreSQL connection, so the application runs as a least-privileged role rather
 * than as the login user that owns the schema.
 *
 * Applies to BOTH connections — the Database and ReportDB have different roles — so the middleware is tagged without
 * a `connection` attribute and the per-connection role is looked up by host:port:dbname inside {@see SetRoleDriver}.
 *
 * The role names still come from the environment rather than from bound parameters, keeping the deployment variables
 * `DOCTRINE_DEFAULT_ROLE` / `DOCTRINE_REPORT_ROLE` (and the host/port/database parts used to key them) under their
 * existing names.
 */
#[Autoconfigure(tags: ['doctrine.middleware'])]
class SetRoleMiddleware implements MiddlewareInterface
{
    #[Override]
    public function wrap(DriverInterface $driver): DriverInterface
    {
        $isPgSQL = $driver instanceof DriverInterface\PDO\PgSQL\Driver;
        if (
            !$isPgSQL
            && !$driver instanceof DriverInterface\PDO\SQLite\Driver
        ) {
            throw new RuntimeException('Expected DBAL Driver to be PDO PgSQL/Sqlite, but got ' . $driver::class);
        }

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
            implode(':', [$roleDefaultHost, $roleDefaultPort, $roleDefaultDB]) => $roleDefaultRole,
            implode(':', [$roleReportHost, $roleReportPort, $roleReportDB]) => $roleReportRole,
        ];

        return new SetRoleDriver($driver, $roles, $isPgSQL);
    }
}
