<?php

namespace Import\Database;

use Doctrine\DBAL\Connection;

class Query
{

    /**
     * Connection.
     *
     * @var Connection
     */
    protected $conn;

    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
