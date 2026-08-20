<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Override;
use RuntimeException;
use SensitiveParameter;

use function implode;
use function sprintf;

/**
 * Opens every connection under the least-privileged PostgreSQL role configured for the database it points at.
 */
class SetRoleDriver extends AbstractDriverMiddleware
{
    /**
     * @param array<non-empty-string, non-empty-string> $roles
     */
    public function __construct(
        DriverInterface $driver,
        private readonly array $roles,
    ) {
        parent::__construct($driver);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function connect(
        #[SensitiveParameter]
        array $params,
    ): ConnectionInterface {
        $connection = parent::connect($params);

        // The connection is identified by where it points rather than by its driver class: doctrine-bundle wraps
        // drivers in middleware of its own, so the concrete driver is not visible here. A connection with no host,
        // port or database name is not addressed that way at all and cannot be matched, so it is left alone.
        if (!isset($params['host'], $params['port'], $params['dbname'])) {
            return $connection;
        }

        $target = implode(
            ':',
            [
                $params['host'],
                $params['port'],
                $params['dbname'],
            ],
        );

        // Both connections are always in the map, because SetRoleMiddleware refuses to build one otherwise. So a
        // connection that is not in it points at a database the `DOCTRINE_*` variables do not describe, which happens
        // when a deployment overrides a DSN without keeping them in step. Carrying on would mean running as the role
        // the connection was opened with, and dropping to a least-privileged one is the only thing this driver is for.
        if (!isset($this->roles[$target])) {
            throw new RuntimeException(sprintf(
                'No role is configured for the database at "%s". The `DOCTRINE_*` environment variables and the DSN'
                . ' they belong to have to describe the same database.',
                $target,
            ));
        }

        $connection->exec('SET ROLE ' . $connection->quote($this->roles[$target]));

        return $connection;
    }
}
