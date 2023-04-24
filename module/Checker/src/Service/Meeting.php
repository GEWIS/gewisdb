<?php

declare(strict_types=1);

namespace Checker\Service;

use Database\Model\Meeting as MeetingModel;
use Database\Service\Meeting as MeetingService;

class Meeting
{
    /**
     * @param MeetingService $meetingService
     */
    public function __construct(private readonly MeetingService $meetingService)
    {
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
