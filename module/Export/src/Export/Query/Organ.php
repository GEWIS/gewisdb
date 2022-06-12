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
                    AND o.orgaanafk = :afk");
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

        $stmt->execute([
            'type' => $type,
            'afk' => $abbr
        ]);

        $res = $stmt->fetchAll();

        if (count($res) == 1) {
            return $res[0]['orgaanid'];
        }
        return null;
    }

    /**
     * Get a function id from a name.
     *
     * @param string $name
     *
     * @return int Function ID
     */
    public function getFunctionId($name)
    {
        $name = strtolower($name);
        // find a corresponding function ID
        // otherwise, default to 'lid'
        $sql = "SELECT functieid FROM functie
            WHERE LOWER(functie) = :functie";
        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute([
            'functie' => $name
        ]);
        $res = $stmt->fetchAll();

        if (count($res) == 1) {
            return $res[0]['functieid'];
        }
        // no results, so use 'lid'
        $stmt->execute([
            'functie' => 'lid'
        ]);
        $res = $stmt->fetchAll();

        return $res[0]['functieid'];
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
