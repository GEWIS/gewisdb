<?php

declare(strict_types=1);

namespace Checker\Service;

use Checker\Mapper\Organ as OrganMapper;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Abrogation as AbrogationModel;
use Database\Model\SubDecision\Foundation as FoundationModel;

use function array_diff;
use function array_map;
use function in_array;
use function sprintf;

class Organ
{
    public function __construct(private readonly OrganMapper $organMapper)
    {
    }

    /**
     * Get the names of all the organs after $meeting
     *
     * @return string[]
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
     * Get the organs that exist after $meeting, by hash.
     *
     * Where {@see self::getAllOrgans()} hands back the hashes on their own, this keeps the foundations themselves, so
     * that what a decision says about an organ can still be read.
     *
     * @return array<string, FoundationModel>
     */
    public function getAllOrganFoundations(MeetingModel $meeting): array
    {
        $abrogated = array_map(
            function (AbrogationModel $organ): string {
                return $this->getHash($organ->getFoundation());
            },
            $this->organMapper->getAllOrganAbrogations($meeting),
        );

        $organs = [];

        foreach ($this->organMapper->getAllOrganFoundations($meeting) as $foundation) {
            $hash = $this->getHash($foundation);

            if (in_array($hash, $abrogated, true)) {
                continue;
            }

            $organs[$hash] = $foundation;
        }

        return $organs;
    }

    /**
     * @return FoundationModel[]
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
            $foundation->getSequence(),
        );
    }
}
