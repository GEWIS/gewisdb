<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use RuntimeException;

use function getenv;
use function implode;

class SetRoleMiddleware implements MiddlewareInterface
{
    public function wrap(DriverInterface $driver): DriverInterface
    {
        $isPgSQL = $driver instanceof DriverInterface\PDO\PgSQL\Driver;
        if (
            !$isPgSQL
            && !$driver instanceof DriverInterface\PDO\SQLite\Driver
        ) {
            throw new RuntimeException('Expected DBAL Driver to be PDO PgSQL/Sqlite, but got ' . $driver::class);
        }

        $roleDefaultHost = getenv('DOCTRINE_DEFAULT_HOST');
        $roleDefaultPort = getenv('DOCTRINE_DEFAULT_PORT');
        $roleDefaultDB   = getenv('DOCTRINE_DEFAULT_DATABASE');
        $roleDefaultRole = getenv('DOCTRINE_DEFAULT_ROLE');

        $roleReportHost = getenv('DOCTRINE_REPORT_HOST');
        $roleReportPort = getenv('DOCTRINE_REPORT_PORT');
        $roleReportDB   = getenv('DOCTRINE_REPORT_DATABASE');
        $roleReportRole = getenv('DOCTRINE_REPORT_ROLE');

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

        return new Driver($driver, $roles, $isPgSQL);
    }
}
