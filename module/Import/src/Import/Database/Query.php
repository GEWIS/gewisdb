<?php

namespace Import\Database;

use Doctrine\DBAL\Connection;

/**
 * Yes, it's quite ugly. However, we only need to import the old database once,
 * so I'm not going to put too much effort into making it nice.
 */
class Query
{

    /**
     * Connection.
     *
     * @var Connection
     */
    protected $conn;

    /**
     * Meeting statement.
     *
     * @var <don't care>
     */
    protected $mStmt;


    /**
     * Prepare and execute the next meeting query.
     */
    protected function prepareMeeting()
    {
        $this->mStmt = $this->getConnection()->prepare("SELECT v.*, t.* FROM vergadering AS v
            INNER JOIN vergadertype AS t ON (v.vergadertypeid = t.vergadertypeid)
            ORDER BY v.datum");
        $this->mStmt->execute();
    }

    /**
     * Fetch the next meeting.
     */
    public function fetchMeeting()
    {
        if (null === $this->mStmt) {
            $this->prepareMeeting();
        }

        return $this->mStmt->fetch();
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
