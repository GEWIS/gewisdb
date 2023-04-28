<?php

declare(strict_types=1);

namespace Checker\Service;

use Database\Model\Meeting as MeetingModel;
use Database\Service\Meeting as MeetingService;

use function array_map;

class Meeting
{
    public function __construct(private readonly MeetingService $meetingService)
    {
    }

    /**
     * Fetch all the existing organs after the meeting.
     * Proxies to Database\Service\Meeting::getAllMeetings()
     *
     * @return MeetingModel[]
     */
    public function getAllMeetings(): array
    {
        $meetings = $this->meetingService->getAllMeetings();

        // Filters out unneeded information
        return array_map(
            static function ($object) {
                return $object[0];
            },
            $meetings,
        );
    }

    public function getLastMeeting(): ?MeetingModel
    {
        return $this->meetingService->getMeetingMapper()->findLast();
    }
}
