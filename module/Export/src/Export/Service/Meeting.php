<?php

namespace Export\Service;

use Application\Service\AbstractService;

use Database\Model\Meeting as MeetingModel;

use Database\Model\SubDecision;

class Meeting extends AbstractService
{

    /**
     * Export meetings.
     */
    public function export()
    {
        $mapper = $this->getMeetingMapper();

        $types = array(
            'bv' => 1,
            'av' => 2,
            'vv' => 3,
            'virt' => 4
        );

        foreach ($mapper->findAll(false) as $meeting) {

            $type = $types[strtolower($meeting->getType())];

            $data = array(
                'vergadertypeid' => $type,
                'vergadernr' => $meeting->getNumber(),
                'datum' => $meeting->getDate()->format('Y-m-d')
            );

            // update the meeting
            if ($this->getQuery()->checkMeetingExists($type, $meeting->getNumber())) {
                $this->getQuery()->updateMeeting($data);
            } else {
                echo 'New meeting ' . $meeting->getType() . ' ' . $meeting->getNumber() . "\n";
                $this->getQuery()->createMeeting($data);
            }

            // export this meeting's decisions
            foreach ($meeting->getDecisions() as $decision) {
                $this->exportDecision($decision, $data);
            }
        }
    }

    /**
     * Export a decision.
     *
     * @param \Database\Model\Decision $decision
     * @param array $meetingData
     */
    protected function exportDecision($decision, $meetingData)
    {
        $meeting = $decision->getMeeting();
        $data = $meetingData;
        $type = $data['vergadertypeid'];
        unset($data['datum']);
        $data['puntnr'] = $decision->getPoint();
        $data['besluitnr'] = $decision->getNumber();

        // gather content from the subdecisions
        $data['inhoud'] = array();
        foreach ($decision->getSubdecisions() as $subdecision) {
            $data['inhoud'][] = $subdecision->getContent();
        }
        $data['inhoud'] = implode($data['inhoud']);
        if ($this->getDecisionQuery()->checkDecisionExists($type, $meeting->getNumber(), $decision->getPoint(), $decision->getNumber())) {
            $this->getDecisionQuery()->updateDecision($data);
        } else {
            $this->getDecisionQuery()->createDecision($data);
        }
        foreach ($decision->getSubdecisions() as $subdecision) {
            $this->exportSubdecision($subdecision, $type);
        }
    }

    /**
     * Export a subdecision.
     *
     * @param \Database\Model\SubDecision $subdecision
     * @param int $type
     */
    protected function exportSubdecision($subdecision, $type)
    {
        $data = array(
            'vergadertypeid' => $type,
            'vergadernr' => $subdecision->getDecision()->getMeeting()->getNumber(),
            'puntnr' => $subdecision->getDecision()->getPoint(),
            'besluitnr' => $subdecision->getDecision()->getNumber(),
            'subbesluitnr' => $subdecision->getNumber(),
            'inhoud' => $subdecision->getContent()
            /*
             * Other fields:
             * - functieid
             * - orgaanid
             * - lidnummer
             */
            // other fields:
            // functieid
            //
        );

        /*
         * 1    Installatie     Het installeren van een GEWIS-lid in een GEWIS-or...
         * 2    Decharge        Het dechargeren van een GEWIS-lid uit een orgaan.
         * 3    Oprichting      Het oprichten van een orgaan.
         * 4    Opheffen        Het opheffen van een orgaan.
         * 5    Begroting       Besluiten met betrekking tot een begroting van ee...
         * 6    Afrekening      Besluiten met betrekking tot een afrekening van e...
         * 7    Overige         Besluiten die niet tot een categorie behoren.
         */

        if ($subdecision instanceof SubDecision\Installation) {
            $data['besluittypeid'] = 1;

            $data['functieid'] = $this->getFunctionId($subdecision);
            $data['orgaanid'] = $this->getOrganId($subdecision->getFoundation());
            $data['lidnummer'] = $subdecision->getMember()->getLidnr();
        } else if ($subdecision instanceof SubDecision\Discharge) {
            $data['besluittypeid'] = 2;

            $data['functieid'] = $this->getFunctionId($subdecision->getInstallation());
            $data['orgaanid'] = $this->getOrganId($subdecision->getInstallation()->getFoundation());
            $data['lidnummer'] = $subdecision->getInstallation()->getMember()->getLidnr();
        } else if ($subdecision instanceof SubDecision\Foundation) {
            $data['besluittypeid'] = 3;

            $data['orgaanid'] = $this->getOrganId($subdecision);
        } else if ($subdecision instanceof SubDecision\Abrogation) {
            $data['besluittypeid'] = 4;

            $data['orgaanid'] = $this->getOrganId($subdecision->getFoundation());
        } else if ($subdecision instanceof SubDecision\Reckoning) {
            $data['besluittypeid'] = 6;

            $data['lidnummer'] = $subdecision->getAuthor()->getLidnr();
        } else if ($subdecision instanceof SubDecision\Budget) {
            $data['besluittypeid'] = 5;

            $data['lidnummer'] = $subdecision->getAuthor()->getLidnr();
        } else if ($subdecision instanceof SubDecision\Other) {
            $data['besluittypeid'] = 7;
            // nothing special
        }

        // TODO: destroyed decisions

        $query = $this->getSubdecisionQuery();

        if ($query->checkSubdecisionExists($type, $data['vergadernr'], $data['puntnr'],
            $data['besluitnr'], $data['subbesluitnr'])) {
            $query->updateSubdecision($data);
        } else {
            $query->createSubdecision($data);
        }
    }

    /**
     * Obtain a functieid from an installation.
     */
    public function getFunctionId(SubDecision\Installation $install)
    {
        $query = $this->getOrganQuery();

        return $query->getFunctionId($install->getFunction());
    }

    /**
     * Obtain organid from a Foundation decision.
     *
     * @param SubDecision\Foundation $foundation
     *
     * @return int the Organ ID
     */
    public function getOrganId(SubDecision\Foundation $organ)
    {
        $query = $this->getOrganQuery();

        // first determine all parameters
        switch ($organ->getOrganType()) {
        case SubDecision\Foundation::ORGAN_TYPE_COMMITTEE:
            $type = 2;
            break;
        case SubDecision\Foundation::ORGAN_TYPE_AV_COMMITTEE:
            $type = 1;
            break;
        case SubDecision\Foundation::ORGAN_TYPE_FRATERNITY:
            $type = 5;
            break;
        }
        $year = $organ->getDecision()->getMeeting()->getDate()->format('Y');

        return $query->checkOrganExists($type, $organ->getAbbr(),
            $organ->getName(), $year);
    }

    /**
     * Get the meeting mapper.
     *
     * @return \Database\Mapper\Meeting
     */
    public function getMeetingMapper()
    {
        return $this->getServiceManager()->get('database_mapper_meeting');
    }

    /**
     * Get the organ query object.
     */
    public function getOrganQuery()
    {
        return $this->getServiceManager()->get('export_query_organ');
    }

    /**
     * Get the subdecision query object.
     */
    public function getSubdecisionQuery()
    {
        return $this->getServiceManager()->get('export_query_subdecision');
    }

    /**
     * Get the decision query object.
     */
    public function getDecisionQuery()
    {
        return $this->getServiceManager()->get('export_query_decision');
    }

    /**
     * Get the query object.
     */
    public function getQuery()
    {
        return $this->getServiceManager()->get('export_query_meeting');
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
