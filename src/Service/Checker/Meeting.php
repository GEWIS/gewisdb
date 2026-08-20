<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Database\Meeting as MeetingModel;
use App\Repository\Database\MeetingRepository;

use function array_map;

class Meeting
{
    public function __construct(private readonly MeetingRepository $meetingRepository)
    {
    }

    /**
     * Fetch all the existing organs after the meeting.
     *
     * @return MeetingModel[]
     */
    public function getAllMeetings(): array
    {
        $meetings = $this->meetingRepository->findAllWithDecisionCount();

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
        return $this->meetingRepository->findLast();
    }
}
