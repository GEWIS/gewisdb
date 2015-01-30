<?php

namespace Export\Service;

use Application\Service\AbstractService;

use Database\Model\Meeting as MeetingModel;

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
                $this->exportDecision($meeting, $decision, $data);
            }
        }
    }

    /**
     * Export a decision.
     *
     * @param \Database\Model\Meeting $meeting
     * @param \Database\Model\Decision $decision
     * @param array $meetingData
     */
    protected function exportDecision($meeting, $decision, $meetingData)
    {
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
