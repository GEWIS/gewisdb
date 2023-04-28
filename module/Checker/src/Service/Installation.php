<?php

declare(strict_types=1);

namespace Checker\Service;

use Checker\Mapper\Installation as InstallationMapper;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Installation as InstallationModel;

use function array_key_exists;
use function sprintf;

class Installation
{
    public function __construct(private readonly InstallationMapper $installationMapper)
    {
    }

    /**
     * Fetch all the existing organs after $meeting
     *
     * @return array<string, InstallationModel>
     */
    public function getAllInstallations(MeetingModel $meeting): array
    {
        $createdMembers = $this->installationMapper->getAllInstallationsInstalled($meeting);
        $deletedMembers = $this->installationMapper->getAllInstallationsDischarged($meeting);

        $members = [];
        foreach ($createdMembers as $cm) {
            $members[$this->getHash($cm)] = $cm;
        }

        foreach ($deletedMembers as $dm) {
            $creation = $dm->getInstallation();
            $hash = $this->getHash($creation);

            if (!isset($members[$hash])) {
                continue;
            }

            unset($members[$hash]);
        }

        return $members;
    }

    /**
     * Returns the different roles for each user in each organ
     *
     * @return array<string, array<int, array<string, InstallationModel>>>
     */
    public function getCurrentRolesPerOrgan(MeetingModel $meeting): array
    {
        $installations = $this->getAllInstallations($meeting);

        $roles = [];

        foreach ($installations as $installation) {
            $memberId = (int) $installation->getMember()->getLidnr();
            $function = $installation->getFunction();
            $organName = $installation->getFoundation()->getAbbr();

            $roles[$organName][$memberId][$function] = $installation;
        }

        return $roles;
    }

    /**
     * Get all members who are currently installed in an organ.
     *
     * @return array<int, string>
     */
    public function getActiveMembers(?MeetingModel $meeting): array
    {
        if (null === $meeting) {
            return [];
        }

        $installations = $this->getAllInstallations($meeting);

        $members = [];
        foreach ($installations as $installation) {
            if ('Inactief Lid' === $installation->getFunction()) {
                continue;
            }

            $member = $installation->getMember()->getLidnr();

            // Doing checks against the keys is a lot faster, and we do not need a lot of information.
            if (array_key_exists($member, $members)) {
                continue;
            }

            $members[$member] = '';
        }

        return $members;
    }

    /**
     * Returns a unique hash for a subdecision (Needed for matching subdecisions)
     */
    private function getHash(InstallationModel $installation): string
    {
        return sprintf(
            '%s-%d.%d.%d.%d',
            $installation->getMeetingType()->value,
            $installation->getMeetingNumber(),
            $installation->getDecisionPoint(),
            $installation->getDecisionNumber(),
            $installation->getNumber(),
        );
    }
}
