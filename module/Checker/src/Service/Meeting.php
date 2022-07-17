<?php

namespace Checker\Service;

use Database\Model\Meeting as MeetingModel;
use Database\Service\Meeting as MeetingService;

class Meeting
{
    /** @var MeetingService $meetingService */
    private $meetingService;

    /**
     * @param MeetingService $meetingService
     */
    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Fetch all the existing organs after the meeting.
     * Proxies to Database\Service\Meeting::getAllMeetings()
     * @return array Database\Model\Meeting
     */
    public function getAllMeetings(): array
    {
        $meetings = $this->meetingService->getAllMeetings();

        // Filters out unneeded information
        return array_map(
            function ($object) {
                return $object[0];
            },
            $meetings,
        );
    }

    /**
     * @return MeetingModel|null
     */
    public function getLastMeeting(): ?MeetingModel
    {
        return $this->meetingService->getMeetingMapper()->findLast();
    }
}
