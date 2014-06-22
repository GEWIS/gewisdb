<?php

namespace Import\Service;

use Application\Service\AbstractService;

class Meeting extends AbstractService
{

    /**
     * Get all meetings.
     */
    public function getMeetings()
    {
        return $this->getQuery()->fetchAllMeetings();
    }

    /**
     * Get all decisions from a meeting.
     *
     * @param array $meeting
     *
     * @return array decisions
     */
    public function getMeetingDecisions($meeting)
    {
        return $this->getQuery()->fetchDecisions(
            $meeting['vergadertypeid'], $meeting['vergadernr']);
    }

    /**
     * Get all subdecisions for a decision.
     *
     * @param array $decision
     *
     * @return array decisions
     */
    public function getSubdecisions($decision)
    {
        return $this->getQuery()->fetchSubdecisions(
            $decision['vergadertypeid'], $decision['vergadernr'],
            $decision['puntnr'], $decision['besluitnr']);
    }

    /**
     * Import a meeting.
     *
     * @param array $meeting
     */
    public function importMeeting($meeting)
    {
        $console = $this->getConsole();

        $decisions = $this->getMeetingDecisions($meeting);

        foreach ($decisions as $decision) {
            var_dump($this->getSubdecisions($decision));
            $console->readChar();
        }

        /*
        $punt = -1;
        $besluit = -1;

        foreach ($rows as $row) {
            if ($row['puntnr'] != $punt || $row['besluitnr'] != $besluit) {
                echo "Besluit " . $meeting['vergaderafk'] . ' ' . $meeting['vergadernr'] . '.' . $row['puntnr'] . '.' . $row['besluitnr'] . "\n";
                $punt = $row['puntnr'];
                $besluit = $row['besluitnr'];
                echo $row['b_inhoud'] . "\n";
            }
            echo $row['subbesluitnr'] . ': ' . $row['inhoud'] . "\n";
            echo "\tType:\t\t{$row['besluittype']}\n";
            echo "\tLid:\t\t{$row['lidnummer']}\n";
            echo "\tFunctie:\t{$row['functie']}\n";
            echo "\tOrgaan:\t\t{$row['orgaanafk']}\n";
            echo "\n";
            $console->readChar();
        }
         */
    }

    /**
     * Get the query object.
     */
    public function getQuery()
    {
        return $this->getServiceManager()->get('import_database_query');
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
