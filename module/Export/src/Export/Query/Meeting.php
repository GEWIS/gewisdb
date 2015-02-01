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
     * Update a meeting.
     *
     * @param array $data meeting data to update
     */
    public function updateMeeting($data)
    {
        $sql = "UPDATE vergadering SET ";
        $cols = array();
        foreach ($data as $key => $val) {
            if ($key != 'vergadertypeid' && $key != 'vergadernr') {
                $cols[] = $key . ' = :' . $key;
            }
        }
        $sql .= implode(', ', $cols);
        $sql .= ' WHERE vergadertypeid = :vergadertypeid
                    AND vergadernr = :vergadernr';

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute($data);
    }

    /**
     * Create a meeting.
     *
     * @param array $data Meeting data to create
     */
    public function createMeeting($data)
    {
        $sql = "INSERT INTO vergadering (";
        $sql .= implode(', ', array_keys($data));
        $sql .= ") VALUES (:";
        $sql .= implode(', :', array_keys($data));
        $sql .= ")";

        $stmt = $this->getConnection()->prepare($sql);

        $stmt->execute($data);
    }

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
