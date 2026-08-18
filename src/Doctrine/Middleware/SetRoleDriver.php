<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Override;
use SensitiveParameter;

use function implode;

/**
 * Renamed from the Laminas-era `Driver`, which under one `App\` namespace read as a generic base class.
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
        // drivers in middleware of its own, so the concrete driver is not visible here. Anything that is not one of
        // the two configured databases keeps the role it connected with.
        if (!isset($params['host'], $params['port'], $params['dbname'])) {
            return $connection;
        }

        $role = $this->roles[implode(':', [$params['host'], $params['port'], $params['dbname']])] ?? null;

        if (null !== $role) {
            $connection->exec('SET ROLE ' . $connection->quote($role));
        }

        return $connection;
    }
}
