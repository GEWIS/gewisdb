<?php

namespace Import\Service;

use Application\Service\AbstractService;

use Database\Model\SubDecision;

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
            //$this->getConsole()->readChar();

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
            // let the code get handled by the specific decision
            switch (strtolower($subdecision['besluittype'])) {
            case 'installatie':
                $this->installationDecision($subdecision);
                break;
            case 'decharge':
                $this->dischargeDecision($subdecision);
                break;
            case 'begroting':
                $this->budgetDecision($subdecision);
                break;
            case 'afrekening':
                $this->reckoningDecision($subdecision);
                break;
            case 'oprichting':
                $this->foundationDecision($subdecision);
                break;
            case 'opheffen':
                $this->abrogationDecision($subdecision);
                break;
            case 'overige':
                $this->otherDecision($subdecision);
                break;
            default:
                var_dump(strtolower($subdecision['besluittype']));
                break;
            }

            //$this->getConsole()->readChar();
        }
    }

    /**
     * Display a subdecision.
     *
     * @param array $subdecision
     */
    protected function displaySubdecision($subdecision)
    {
        echo $subdecision['subbesluitnr'] . ': ' . $subdecision['inhoud'] . "\n";
        echo "\tType:\t\t{$subdecision['besluittype']}\n";
        echo "\tLid:\t\t{$subdecision['lidnummer']}\n";
        echo "\tFunctie:\t{$subdecision['functie']}\n";
        echo "\tOrgaan:\t\t{$subdecision['orgaanafk']}\n";
        echo "\n";
    }

    /**
     * Installation decision.
     *
     * @param array $subdecision
     */
    protected function installationDecision($subdecision)
    {
        // TODO: implement this
    }

    /**
     * Discharge decision.
     *
     * @param array $subdecision
     */
    protected function dischargeDecision($subdecision)
    {
        // TODO: implement this
    }

    /**
     * Budget decision.
     *
     * @param array $subdecision
     * @param string $type
     */
    protected function budgetDecision($subdecision, $type = 'budget')
    {
        $this->displaySubdecision($subdecision);

        if ($type == 'budget') {
            $model = new SubDecision\Budget();
        } else {
            $model = new SubDecision\Reckoning();
        }

        if (empty($subdecision['lidnummer'])) {
            echo "Er kon geen lidnummer in de metadata gevonden worden\n";
            echo "Als er wel een lid vernoemd wordt, geef het lidnummer: ";
            $subdecision['lidnummer'] = $this->getConsole()->readLine();
        }
        if (!empty($subdecision['lidnummer'])) {
            // find member and add to subdecision
            $member = $this->findMember($subdecision['lidnummer']);
            if (!empty($member)) {
                $model->setAuthor($member);
            }
        }

        if (!empty($subdecision['orgaanafk'])) {
            // search for organ in current database
            // and interactively check if it is the correct one
            $organ = $this->searchOrgan($subdecision['orgaanafk']);
            if (!empty($organ)) {
                $model->setFoundation($organ);
            }
        }

        // extract version, date, approval and changes

        $console = $this->getConsole();

        echo "\nHet is niet mogelijk om de volgende informatie automatisch uit het besluit te halen. Hence, vul deze correct in.\n";
        echo "(leeglaten indien onbeschikbaar)\n\n";

        echo "Naam: ";
        $model->setName(trim($console->readLine()));

        echo "Versienummer: ";
        $version = trim($console->readLine());
        if (empty($version)) {
            $version = 'onbekend';
        }
        $model->setVersion($version);

        echo "Datum van *begroting/afrekening* [YYYY-MM-DD]: ";
        $date = trim($console->readLine());
        if (empty($date)) {
            // use the meeting date
            $model->setDate(new \DateTime($subdecision['datum']));
        } else {
            $model->setDate(new \DateTime($date));
        }

        echo "Goedgekeurd [Y/n]: ";
        $model->setApproval(strtolower(trim($console->readLine())) != 'n');

        echo "Met wijzigingen [y/N]: ";
        $model->setChanges(strtolower(trim($console->readLine())) == 'y');

        echo "\n";

        echo $model->getContent() . "\n";

        $this->getConsole()->readChar();
    }

    /**
     * Reckoning decision.
     *
     * @param array $subdecision
     */
    protected function reckoningDecision($subdecision)
    {
        $this->budgetDecision($subdecision, 'reckoning');
    }

    /**
     * Foundation decision.
     *
     * @param array $subdecision
     */
    protected function foundationDecision($subdecision)
    {
        // TODO: implement this
    }

    /**
     * Abrogation decision.
     *
     * @param array $subdecision
     */
    protected function abrogationDecision($subdecision)
    {
        // TODO: implement this
    }

    /**
     * Other decision.
     *
     * @param array $subdecision
     */
    protected function otherDecision($subdecision)
    {
        // TODO: implement this
    }

    /**
     * (Interactively) search for an organ.
     *
     * @param string $query
     *
     * @return Database\Model\SubDecision\Foundation
     */
    protected function searchOrgan($query)
    {
        $results = $this->getOrganMapper()->organSearch($query, true);

        if (empty($results)) {
            return;
        }

        echo "\n";
        foreach ($results as $key => $foundation) {
            echo "\t$key) " . $foundation->getAbbr() . "\n";
        }
        echo "\nWelke van deze organen is het genoemde orgaan ($query)? ";

        $num = (int) trim($this->getConsole()->readLine());

        return $results[$num];
    }

    /**
     * Find a member.
     *
     * @param string $lidnr
     *
     * @return Database\Model\Member
     */
    protected function findMember($lidnr)
    {
        return $this->getMemberMapper()->find($lidnr);
    }

    /**
     * Get the organ mapper.
     *
     * @return Database\Mapper\Organ
     */
    public function getOrganMapper()
    {
        return $this->getServiceManager()->get('database_mapper_organ');
    }

    /**
     * Get the member mapper.
     *
     * @return Database\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->getServiceManager()->get('database_mapper_member');
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
