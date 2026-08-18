<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision\Installation as InstallationModel;
use App\Repository\Checker\InstallationRepository;

use function array_key_exists;
use function sprintf;

class Installation
{
    public function __construct(private readonly InstallationRepository $installationRepository)
    {
    }

    /**
     * Fetch all the existing organs after $meeting
     *
     * @return array<string, InstallationModel>
     */
    public function getAllInstallations(MeetingModel $meeting): array
    {
        $createdMembers = $this->installationRepository->getAllInstallationsInstalled($meeting);
        $deletedMembers = $this->installationRepository->getAllInstallationsDischarged($meeting);

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
            $memberId = $installation->getMember()->getLidnr();
            $function = $installation->getFunction()->value;
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
            if (InstallationFunctions::InactiveMember === $installation->getFunction()) {
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
            $installation->getSequence(),
        );
    }
}
