<?php

namespace Checker\Service;

use Application\Service\AbstractService;

class Meeting extends AbstractService
{
    /**
     * Fetch all the existing organs after the meeting.
     * Proxies to Database\Service\Meeting::getAllMeetings()
     * @return array Database\Model\Meeting
     */
    public function getAllMeetings()
    {
        $databaseServiceMeeting = $this->getServiceManager()->get('database_service_meeting');
        $meetings = $databaseServiceMeeting->getAllMeetings();
        // Filters out unneeded information
        return array_map(
            function ($object) {
                return $object[0];
            },
            $meetings
        );
    }

    /**
     * @return \Database\Model\Meeting|null
     */
    public function getLastMeeting()
    {
        $databaseServiceMeeting = $this->getServiceManager()->get('database_service_meeting');
        $meetingMapper = $databaseServiceMeeting->getMeetingMapper();

        return $meetingMapper->findLast();
    }
}
