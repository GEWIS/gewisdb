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

        foreach ($mapper->findAll(false) as $meeting) {
            echo 'Exporting ' . $meeting->getType() . ' ' . $meeting->getNumber() . "\n";
            // TODO export $meeting to the old database
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
