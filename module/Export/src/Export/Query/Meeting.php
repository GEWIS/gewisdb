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

    /**
     * Meeting exists statement.
     */
    protected $existsStmt;


    /**
     * Prepare the meeting exists statement.
     */
    public function getExistsStmt()
    {
        if (null === $this->existsStmt) {
            $this->existsStmt = $this->getConnection()->prepare("
                SELECT m.vergadertypeid FROM vergadering AS m
                WHERE m.vergadertypeid = :type
                    AND m.vergadernr = :number");
        }
        return $this->existsStmt;
    }

    /**
     * Check if the meeting exists.
     *
     * @param int $type
     * @param int $number
     *
     * @return boolean If exists
     */
    public function checkMeetingExists($type, $number)
    {
        $stmt = $this->getExistsStmt();

        $stmt->execute(array(
            'type' => $type,
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
