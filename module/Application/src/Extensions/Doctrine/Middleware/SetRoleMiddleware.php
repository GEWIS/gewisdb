<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use RuntimeException;

use function getenv;

class SetRoleMiddleware implements MiddlewareInterface
{
    public function wrap(DriverInterface $driver): DriverInterface
    {
        $isPgSQL = $driver instanceof DriverInterface\PDO\PgSQL\Driver;
        if (
            !$isPgSQL
            && !$driver instanceof DriverInterface\PDO\SQLite\Driver
        ) {
            throw new RuntimeException('Expected DBAL Driver to be PDO PgSQL, but got ' . $driver::class);
        }

        $role = getenv('DOCTRINE_ROLE');
        if (false === $role) {
            throw new RuntimeException('Required DOCTRINE_ROLE not set...');
        }

        return new Driver($driver, $role, $isPgSQL);
    }
}
