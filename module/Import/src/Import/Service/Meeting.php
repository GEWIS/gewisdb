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
        $decisions = $this->getMeetingDecisions($meeting);

        foreach ($decisions as $decision) {
            echo "Besluit " . $meeting['vergaderafk'] . ' ' . $meeting['vergadernr']
                . '.' . $decision['puntnr'] . '.' . $decision['besluitnr'] . "\n";

            $punt = $decision['puntnr'];
            $besluit = $decision['besluitnr'];
            echo $decision['inhoud'] . "\n";
            echo "----\n";

            $this->importDecision($decision);
            $this->getConsole()->readChar();

            echo "----\n";
        }
    }

    /**
     * Import a decision.
     *
     * @param array $decision
     */
    protected function importDecision($decision)
    {
        $subdecisions = $this->getSubdecisions($decision);

        foreach ($subdecisions as $subdecision) {
            echo $subdecision['subbesluitnr'] . ': ' . $subdecision['inhoud'] . "\n";
            echo "\tType:\t\t{$subdecision['besluittype']}\n";
            echo "\tLid:\t\t{$subdecision['lidnummer']}\n";
            echo "\tFunctie:\t{$subdecision['functie']}\n";
            echo "\tOrgaan:\t\t{$subdecision['orgaanafk']}\n";
            echo "\n";
            $this->getConsole()->readChar();
        }
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
