<?php

namespace Export\Query;

use Doctrine\DBAL\Connection;

/**
 * Class for member queries.
 */
class Member
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
