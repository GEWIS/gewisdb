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

    /**
     * Member exists statement.
     */
    protected $existsStmt;


    /**
     * Prepare the member exists statement.
     */
    public function getExistsStmt()
    {
        if (null === $this->existsStmt) {
            $this->existsStmt = $this->getConnection()->prepare("
                SELECT m.lidnummer FROM gewis_lid AS m
                WHERE m.lidnummer = :lidnr");
        }
        return $this->existsStmt;
    }


    /**
     * Check if the member exists.
     *
     * @param int $lidnr
     *
     * @return boolean If exists
     */
    public function checkMemberExists($lidnr)
    {
        $stmt = $this->getExistsStmt();

        $stmt->execute(array(
            'lidnr' => $lidnr
        ));

        return count($stmt->fetchAll()) == 1;
    }

    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
