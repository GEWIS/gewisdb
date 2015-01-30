<?php

namespace Export\Query;

use Doctrine\DBAL\Connection;

/**
 * Class for organ queries.
 */
class Organ
{

    /**
     * Connection.
     *
     * @var Connection
     */
    protected $conn;

    /**
     * Organ exists statement.
     */
    protected $existsStmt;


    /**
     * Create a organ.
     *
     * @param array $data Organ data to create
     */
    public function createOrgan($data)
    {
        $sql = "INSERT INTO orgaan (";
        $sql .= implode(', ', array_keys($data));
        $sql .= ") VALUES (:";
        $sql .= implode(', :', array_keys($data));
        $sql .= ")";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute($data);
    }

    /**
     * Prepare the organ exists statement.
     */
    public function getExistsStmt()
    {
        if (null === $this->existsStmt) {
            $this->existsStmt = $this->getConnection()->prepare("
                SELECT o.orgaanid FROM orgaan AS o
                WHERE o.orgaantypeid = :type
                    AND o.orgaanafk = :afk
                    AND o.orgaannaam = :naam
                    AND o.jaartal = :jaar");
        }
        return $this->existsStmt;
    }

    /**
     * Check if the organ exists.
     *
     * @param int $type
     * @param string $abbr
     * @param string $name
     * @param string $year
     *
     * @return boolean If exists
     */
    public function checkOrganExists($type, $abbr, $name, $year)
    {
        $stmt = $this->getExistsStmt();

        $stmt->execute(array(
            'type' => $type,
            'afk' => $abbr,
            'naam' => $name,
            'jaar' => $year
        ));

        $res = $stmt->fetchAll();

        if (count($res) == 1) {
            return $res[0]['orgaanid'];
        }
        return null;
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
