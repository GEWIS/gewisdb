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
     * Decision statement.
     *
     * @var <don't care>
     */
    protected $dStmt;

    /**
     * Subdecision statement.
     *
     * @var <don't care>
     */
    protected $sStmt;


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
     *
     * @return array
     */
    public function fetchMeeting()
    {
        if (null === $this->mStmt) {
            $this->prepareMeeting();
        }

        return $this->mStmt->fetch();
    }

    /**
     * Fetch the next meeting.
     *
     * @return array
     */
    public function fetchAllMeetings()
    {
        if (null === $this->mStmt) {
            $this->prepareMeeting();
        }

        return $this->mStmt->fetchAll();
    }

    /**
     * Prepare the decisions query.
     */
    protected function prepareDecisions()
    {
        $this->dStmt = $this->getConnection()->prepare("SELECT b.*  FROM besluit AS b
            WHERE b.vergadertypeid = :type AND b.vergadernr = :nr
            ORDER BY b.puntnr ASC, b.besluitnr ASC");
    }

    /**
     * Fetch all decisions and subdecisions in a meeting.
     *
     * @param string $type
     * @param string $nr
     *
     * @return array
     */
    public function fetchDecisions($type, $nr)
    {
        if (null === $this->dStmt) {
            $this->prepareDecisions();
        }

        $this->dStmt->execute(array(
            'type' => $type,
            'nr' => $nr
        ));

        return $this->dStmt->fetchAll();
    }

    /**
     * Prepare the subdecisions query.
     */
    protected function prepareSubdecisions()
    {
        $this->sStmt = $this->getConnection()->prepare("SELECT s.*, bs.*, f.*, o.*
            FROM subbesluit AS s
            INNER JOIN besluittype AS bs ON (s.besluittypeid = bs.besluittypeid)
            LEFT JOIN functie AS f ON (f.functieid = s.functieid)
            LEFT JOIN orgaan AS o ON (o.orgaanid = s.orgaanid)
            WHERE s.vergadertypeid = :type AND s.vergadernr = :nr
                AND s.puntnr = :puntnr AND s.besluitnr = :besluitnr
            ORDER BY s.puntnr ASC, s.besluitnr ASC, s.subbesluitnr ASC");
    }

    /**
     * Fetch all decisions and subdecisions in a meeting.
     *
     * @param string $type
     * @param string $nr
     * @param string $puntnr
     * @param string $besluitnr
     *
     * @return array
     */
    public function fetchSubdecisions($type, $nr, $puntnr, $besluitnr)
    {
        if (null === $this->sStmt) {
            $this->prepareSubdecisions();
        }

        $this->sStmt->execute(array(
            'type' => $type,
            'nr' => $nr,
            'puntnr' => $puntnr,
            'besluitnr' => $besluitnr
        ));

        return $this->sStmt->fetchAll();
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
