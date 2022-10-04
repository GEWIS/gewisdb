<?php

namespace Checker\Service;

use Checker\Mapper\Organ as OrganMapper;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Abrogation as AbrogationModel;
use Database\Model\SubDecision\Foundation as FoundationModel;

class Organ
{
    public function __construct(private readonly OrganMapper $organMapper)
    {
    }

    /**
     * Get the names of all the organs after $meeting
     *
     * @param MeetingModel $meeting
     *
     * @return array string
     */
    public function getAllOrgans(MeetingModel $meeting): array
    {
        $organFoundations = $this->organMapper->getAllOrganFoundations($meeting);
        $organAbrogations = $this->organMapper->getAllOrganAbrogations($meeting);

        $hashedOrganFoundations = array_map(
            function (FoundationModel $organ) {
                return $this->getHash($organ);
            },
            $organFoundations,
        );

        $hashedOrganAbrogations = array_map(
            function (AbrogationModel $organ) {
                return $this->getHash($organ->getFoundation());
            },
            $organAbrogations,
        );

        return array_diff($hashedOrganFoundations, $hashedOrganAbrogations);
    }

    /**
     * @return array<array-key, FoundationModel>
     */
    public function getOrgansCreatedAtMeeting(MeetingModel $meeting): array
    {
        return $this->organMapper->getOrgansCreatedAtMeeting($meeting);
    }

    public function getHash(FoundationModel $foundation): string
    {
        return sprintf(
            '%s-%d.%d.%d.%d',
            $foundation->getMeetingType()->value,
            $foundation->getMeetingNumber(),
            $foundation->getDecisionPoint(),
            $foundation->getDecisionNumber(),
            $foundation->getNumber(),
        );
    }
}
