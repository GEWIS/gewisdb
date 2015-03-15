<?php

namespace Export\Query;

use Doctrine\DBAL\Connection;

/**
 * Class for subdecision queries.
 */
class SubDecision
{

    /**
     * Connection.
     *
     * @var Connection
     */
    protected $conn;

    /**
     * Decision exists statement.
     */
    protected $existsStmt;


    /**
     * Update a subdecision.
     *
     * @param array $data subdecision data to update
     */
    public function updateSubdecision($data)
    {
        $sql = "UPDATE subbesluit SET ";
        $cols = array();
        foreach ($data as $key => $val) {
            if ($key != 'vergadertypeid' && $key != 'vergadernr' && $key != 'puntnr' && $key != 'besluitnr' && $key != 'subbesluitnr') {
                $cols[] = $key . ' = :' . $key;
            }
        }
        $sql .= implode(', ', $cols);
        $sql .= ' WHERE vergadertypeid = :vergadertypeid
                    AND vergadernr = :vergadernr
                    AND puntnr = :puntnr
                    AND besluitnr = :besluitnr
                    AND subbesluitnr = :subbesluitnr';

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute($data);
    }

    /**
     * Create a subdecision.
     *
     * @param array $data Subdecision data to create
     */
    public function createSubdecision($data)
    {
        $sql = "INSERT INTO subbesluit (";
        $sql .= implode(', ', array_keys($data));
        $sql .= ") VALUES (:";
        $sql .= implode(', :', array_keys($data));
        $sql .= ")";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute($data);
    }

    /**
     * Prepare the decision exists statement.
     */
    public function getExistsStmt()
    {
        if (null === $this->existsStmt) {
            $this->existsStmt = $this->getConnection()->prepare("
                SELECT b.vergadertypeid FROM subbesluit AS b
                WHERE b.vergadertypeid = :type
                    AND b.vergadernr = :meetingnumber
                    AND b.puntnr = :point
                    AND b.besluitnr = :decnumber
                    AND b.subbesluitnr = :number");
        }
        return $this->existsStmt;
    }

    /**
     * Check if the decision exists.
     *
     * @param int $type
     * @param int $meetingnumber
     * @param int $point
     * @param int $decnumber
     * @param int $number
     *
     * @return boolean If exists
     */
    public function checkSubdecisionExists($type, $meetingnumber, $point, $decnumber, $number)
    {
        $stmt = $this->getExistsStmt();

        $stmt->execute(array(
            'type' => $type,
            'meetingnumber' => $meetingnumber,
            'point' => $point,
            'decnumber' => $decnumber,
            'number' => $number
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
