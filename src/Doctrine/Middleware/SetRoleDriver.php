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
 * The driver {@see SetRoleMiddleware} wraps around the real one.
 *
 * Renamed from the Laminas-era `Driver`: under one `App\` namespace a class called `Driver` sitting next to a class
 * called `SetRoleMiddleware` reads as a generic base rather than as the specific thing it is.
 */
class SetRoleDriver extends AbstractDriverMiddleware
{
    /**
     * @param array<non-empty-string, non-empty-string> $roles
     */
    public function __construct(
        DriverInterface $driver,
        private readonly array $roles,
        private readonly bool $isPgSQL,
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

        if (
            $this->isPgSQL
            && isset($params['host'], $params['port'], $params['dbname'])
        ) {
            $role = $this->roles[implode(':', [$params['host'], $params['port'], $params['dbname']])];

            $connection->exec('SET ROLE ' . $connection->quote($role));
        }

        return $connection;
    }
}
