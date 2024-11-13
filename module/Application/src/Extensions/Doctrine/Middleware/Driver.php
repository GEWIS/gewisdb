<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use SensitiveParameter;

use function implode;

class Driver extends AbstractDriverMiddleware
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
