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
        } else if ($subdecision instanceof SubDecision\Discharge) {
            $data['besluittypeid'] = 2;
        } else if ($subdecision instanceof SubDecision\Foundation) {
            $data['besluittypeid'] = 3;
        } else if ($subdecision instanceof SubDecision\Abrogation) {
            $data['besluittypeid'] = 4;
        } else if ($subdecision instanceof SubDecision\Reckoning) {
            $data['besluittypeid'] = 6;
        } else if ($subdecision instanceof SubDecision\Budget) {
            $data['besluittypeid'] = 5;
        } else if ($subdecision instanceof SubDecision\Other) {
            $data['besluittypeid'] = 7;
        }
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
