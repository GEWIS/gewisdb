<?php

namespace Export\Query;

use Doctrine\DBAL\Connection;

/**
 * Class for meeting queries.
 */
class Meeting
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
