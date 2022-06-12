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
     * Update a member.
     *
     * @param array $data member data to update
     */
    public function updateMember($data)
    {
        $sql = "UPDATE gewis_lid SET ";
        $cols = [];
        foreach ($data as $key => $val) {
            if ($key != 'lidnummer') {
                $cols[] = $key . ' = :' . $key;
            }
        }
        $sql .= implode(', ', $cols);
        $sql .= ' WHERE lidnummer = :lidnummer';

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute($data);
    }

    /**
     * Create a member.
     *
     * @param array $data Member data to create
     */
    public function createMember($data)
    {
        $sql = "INSERT INTO gewis_lid (";
        $sql .= implode(', ', array_keys($data));
        $sql .= ") VALUES (:";
        $sql .= implode(', :', array_keys($data));
        $sql .= ")";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute($data);
    }

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

        $stmt->execute([
            'lidnr' => $lidnr
        ]);

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
