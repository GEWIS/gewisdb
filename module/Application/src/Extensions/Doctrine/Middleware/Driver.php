<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use SensitiveParameter;

class Driver extends AbstractDriverMiddleware
{
    public function __construct(
        DriverInterface $driver,
        private readonly string $role,
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

        if ($this->isPgSQL) {
            $connection->exec('SET ROLE ' . $connection->quote($this->role));
        }

        return $connection;
    }
}
