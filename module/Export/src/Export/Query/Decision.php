<?php

namespace Export\Query;

use Doctrine\DBAL\Connection;

/**
 * Class for meeting queries.
 */
class Decision
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
     * Update a decision.
     *
     * @param array $data decision data to update
     */
    public function updateDecision($data)
    {
        $sql = "UPDATE besluit SET ";
        $cols = [];
        foreach ($data as $key => $val) {
            if ($key != 'vergadertypeid' && $key != 'vergadernr' && $key != 'puntnr' && $key != 'besluitnr') {
                $cols[] = $key . ' = :' . $key;
            }
        }
        $sql .= implode(', ', $cols);
        $sql .= ' WHERE vergadertypeid = :vergadertypeid
                    AND vergadernr = :vergadernr
                    AND puntnr = :puntnr
                    AND besluitnr = :besluitnr';

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute($data);
    }

    /**
     * Create a decision.
     *
     * @param array $data Decision data to create
     */
    public function createDecision($data)
    {
        $sql = "INSERT INTO besluit (";
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
                SELECT b.vergadertypeid FROM besluit AS b
                WHERE b.vergadertypeid = :type
                    AND b.vergadernr = :meetingnumber
                    AND b.puntnr = :point
                    AND b.besluitnr = :number");
        }
        return $this->existsStmt;
    }

    /**
     * Check if the decision exists.
     *
     * @param int $type
     * @param int $meetingnumber
     * @param int $point
     * @param int $number
     *
     * @return boolean If exists
     */
    public function checkDecisionExists($type, $meetingnumber, $point, $number)
    {
        $stmt = $this->getExistsStmt();

        $stmt->execute([
            'type' => $type,
            'meetingnumber' => $meetingnumber,
            'point' => $point,
            'number' => $number
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
