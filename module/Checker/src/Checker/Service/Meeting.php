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
    public function getAllMeetings(\DateTime $startTime)
    {
        $databaseServiceMeeting = $this->getServiceManager()->get('database_service_meeting');
        $meetings = $databaseServiceMeeting->getAllMeetings();

        // Filters out unneeded information
        $meetings = array_map(
            function ($object) {
                return $object[0];
            },
            $meetings
        );

        // Filter out to old meetings
        $meetings = array_filter($meetings, function(\Database\Model\Meeting $meeting)  use ($startTime) {
            return $meeting->getDate() >= $startTime;
        });

        return $meetings;
    }
}
