<?php

namespace Import\Service;

use Application\Service\AbstractService;

use Database\Model\SubDecision;
use Database\Model\Decision;
use Database\Model\Meeting as MeetingModel;

class Meeting extends AbstractService
{

    /**
     * Previous found foundation decision.
     *
     * @var SubDecision\Foundation
     */
    protected $prevFoundation;

    /**
     * Foundations that have just been added.
     *
     * @var array of SubDecision\Foundation
     */
    protected $addedFoundations = array();

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
     *
     * @return MeetingModel
     */
    public function importMeeting($meeting)
    {
        // check if the meeting already exists
        if (null !== $this->getMeetingMapper()->find(strtolower($meeting['vergaderafk']), (int) $meeting['vergadernr'])) {
            // meeting already exists in database
            return;
        }

        $decisions = $this->getMeetingDecisions($meeting);

        $newMeeting = new MeetingModel();

        $newMeeting->setType(strtolower($meeting['vergaderafk']));
        $newMeeting->setNumber((int) $meeting['vergadernr']);
        $newMeeting->setDate(new \DateTime($meeting['datum']));

        foreach ($decisions as $decision) {
            echo "Besluit " . $meeting['vergaderafk'] . ' ' . $meeting['vergadernr']
                . '.' . $decision['puntnr'] . '.' . $decision['besluitnr'] . "\n";

            $punt = $decision['puntnr'];
            $besluit = $decision['besluitnr'];
            echo $decision['inhoud'] . "\n";
            echo "------------------------------------------------------------------------------\n";

            $model = $this->importDecision($decision, $newMeeting);
            echo "\n";
        }

        // TODO: persist
        $this->getMeetingMapper()->persist($newMeeting);
        return $newMeeting;
    }

    /**
     * Import a decision.
     *
     * @param array $decision
     * @param MeetingModel $newMeeting
     *
     * @return Decision
     */
    protected function importDecision($decision, $newMeeting)
    {
        $subdecisions = $this->getSubdecisions($decision);

        $newDecision = new Decision();

        $newDecision->setPoint($decision['puntnr']);
        $newDecision->setNumber($decision['besluitnr']);

        $newDecision->setMeeting($newMeeting);

        foreach ($subdecisions as $subdecision) {
            $model = null;
            // let the code get handled by the specific decision
            switch (strtolower($subdecision['besluittype'])) {
            case 'installatie':
                $model = $this->installationDecision($subdecision);
                break;
            case 'decharge':
                $model = $this->dischargeDecision($subdecision);
                break;
            case 'begroting':
                $model = $this->budgetDecision($subdecision);
                break;
            case 'afrekening':
                $model = $this->reckoningDecision($subdecision);
                break;
            case 'oprichting':
                $model = $this->foundationDecision($subdecision);
                break;
            case 'opheffen':
                $model = $this->abrogationDecision($subdecision);
                break;
            case 'overige':
                $model = $this->otherDecision($subdecision);
                break;
            default:
                var_dump(strtolower($subdecision['besluittype']));
                break;
            }

            if ($model instanceof SubDecision) {
                $model->setDecision($newDecision);
                $model->setNumber($subdecision['subbesluitnr']);
            }
        }

        return $newDecision;
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
        $this->displaySubdecision($subdecision);

        $install = new SubDecision\Installation();

        $member = $this->searchMember($subdecision['lidnummer']);
        if (empty($member)) {
            echo "Geen lid gevonden. Subbesluit niet toegevoegd.\n\n";
            return null;
        }
        $install->setMember($member);
        $organ = $this->searchOrgan($subdecision['orgaanafk']);
        if (empty($organ)) {
            echo "Geen orgaan gevonden. Subbesluit niet toegevoegd.\n\n";
            return null;
        }
        $install->setFoundation($organ);
        $install->setFunction($this->findFunction($subdecision['functie']));

        echo $install->getContent() . "\n";

        return $install;
    }

    /**
     * Discharge decision.
     *
     * @param array $subdecision
     */
    protected function dischargeDecision($subdecision)
    {
        $this->displaySubdecision($subdecision);

        $foundation = $this->searchOrgan($subdecision['orgaanafk']);

        if (empty($foundation)) {
            return null;
        }

        $candidates = array();

        foreach ($foundation->getReferences() as $reference) {
            if ($reference instanceof SubDecision\Installation) {
                if ($reference->getMember()->getLidnr() == $subdecision['lidnummer']) {
                    $candidates[] = $reference;
                }
            }
        }

        if (empty($candidates)) {
            return null;
        }

        echo "\nSelecteer de installatie:\n\n";

        foreach ($candidates as $key => $candidate) {
            echo "\t$key) " . $candidate->getFunction() . " " . $candidate->getMember()->getFullName() . " (" . $candidate->getMember()->getLidnr() . ")\n";
        }

        $num = (int) trim($this->getConsole()->readLine());

        $installation = $candidates[$num];

        $discharge = new SubDecision\Discharge();

        $discharge->setInstallation($installation);

        echo $discharge->getContent() . "\n";

        return $discharge;
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

        echo "\nHet is niet mogelijk om de volgende informatie automatisch uit het besluit te halen. Hence, vul deze correct in.\n";
        echo "(leeglaten indien onbeschikbaar)\n\n";

        $member = $this->searchMember($subdecision['lidnummer']);
        if (!empty($member)) {
            $model->setAuthor($member);
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

        echo "\n";

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

        return $model;
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
        $foundation = new SubDecision\Foundation();

        $foundation->setName($subdecision['orgaannaam']);
        $foundation->setAbbr($subdecision['orgaanafk']);

        switch (strtolower($subdecision['orgaantype'])) {
        case 'av-commissie':
            $foundation->setOrganType(SubDecision\Foundation::ORGAN_TYPE_AV_COMMITTEE);
            break;
        case 'dispuut':
            $foundation->setOrganType(SubDecision\Foundation::ORGAN_TYPE_FRATERNITY);
            break;
        case 'bv-commissie':
        default:
            $foundation->setOrganType(SubDecision\Foundation::ORGAN_TYPE_COMMITTEE);
            break;
        }

        // for if it is abrogated in the next subdecision
        $this->prevFoundation = $foundation;
        $this->addedFoundations[] = $foundation;

        return $foundation;
    }

    /**
     * Abrogation decision.
     *
     * @param array $subdecision
     */
    protected function abrogationDecision($subdecision)
    {
        $this->displaySubdecision($subdecision);

        $foundation = $this->searchOrgan($subdecision['orgaanafk']);

        // special handling code for decisions where the organ gets created
        // and abrogated within the same decision (also called hacks)
        if (empty($foundation) && !empty($this->prevFoundation) && $this->prevFoundation->getAbbr() == $subdecision['orgaanafk']) {
            $foundation = $this->prevFoundation;
        }
        if (empty($foundation)) {
            echo "Warning: no abrogation decision made, since the organ has not been founded.";
            $this->getConsole()->readChar();
            return;
        }

        $abrogation = new SubDecision\Abrogation();

        $abrogation->setFoundation($foundation);

        echo $abrogation->getContent();
        $this->getConsole()->readChar();

        return $abrogation;
    }

    /**
     * Other decision.
     *
     * @param array $subdecision
     */
    protected function otherDecision($subdecision)
    {
        // simply get the content and we're done
        $other = new SubDecision\Other();

        $other->setContent($subdecision['inhoud']);

        return $other;
    }

    /**
     * Get the correct function.
     *
     * @param string $function
     *
     * @return correct function name
     */
    protected function findFunction($function)
    {
        switch (strtolower($function)) {
        case 'voorzitter':
            return 'Voorzitter';
            break;
        case 'secretaris':
            return 'Secretaris';
            break;
        case 'penningmeester':
            return 'Penningmeester';
            break;
        case 'vice-voorzitter':
            return 'Vice-Voorzitter';
            break;
        case 'pr-functionaris':
            return 'PR-Functionaris';
            break;
        case 'onderwijs commissaris':
            return 'Onderwijscommissaris';
            break;
        case 'lid':
            return 'Lid';
            break;
        case 'inkoper':
            return 'Inkoper';
            break;
        case 'tafelvoetbalcoordinator':
            return 'Tafelvoetbalcoordinator';
            break;
        }
    }

    /**
     * (Interactively) search for a user.
     *
     * @param string $lidnr
     *
     * @return Database\Model\Member
     */
    protected function searchMember($lidnr)
    {
        if (empty($lidnr)) {
            echo "Er kon geen lidnummer in de metadata gevonden worden\n";
            echo "Als er wel een lid vernoemd wordt, geef het lidnummer: ";
            $lidnr = $this->getConsole()->readLine();
        }
        if (!empty($lidnr)) {
            // find member and add to subdecision
            return $this->findMember($lidnr);
        }
    }

    /**
     * Find an added foundation.
     *
     * @param string $query
     *
     * @return array of Database\Model\SubDecision\Foundation
     */
    protected function findAddedFoundation($query)
    {
        $found = array();
        foreach ($this->addedFoundations as $foundation) {
            if ($foundation->getAbbr() == $query || $foundation->getName() == $query) {
                $found[] = $foundation;
            }
        }
        return $found;
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
            // try to find in local storage
            $results = $this->findAddedFoundation($query);

            if (empty($results)) {
                return;
            }
        }

        // see if we find a perfect match
        if (count($results) == 1) {
            return $results[0];
        }
        foreach ($results as $foundation) {
            if ($foundation->getAbbr() == $query) {
                return $foundation;
            }
        }

        echo "\n";
        foreach ($results as $key => $foundation) {
            echo "\t$key) " . $foundation->getName() . ' (' . $foundation->getAbbr() . ")\n";
        }
        echo "\nWelke van deze organen is het genoemde orgaan? ";

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
     * Get the meeting mapper.
     *
     * @return Database\Mapper\Meeting
     */
    public function getMeetingMapper()
    {
        return $this->getServiceManager()->get('database_mapper_meeting');
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
