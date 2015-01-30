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
            echo 'Exporting ' . $meeting->getType() . ' ' . $meeting->getNumber() . "\n";

            $type = $types[strtolower($meeting->getType())];

            if ($this->getQuery()->checkMeetingExists($type, $meeting->getNumber())) {
                echo 'Already exists';
            } else {
                // add to DB
                // Y-m-d
                $data = array(
                    'vergadertypeid' => $type,
                    'vergadernr' => $meeting->getNumber(),
                    'datum' => $meeting->getDate()->format('Y-m-d')
                );

                $this->getQuery()->createMeeting($data);
            }
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
