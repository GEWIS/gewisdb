<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision\Installation as InstallationModel;
use App\Repository\Checker\InstallationRepository;

class Installation
{
    /**
     * The installations of the meeting most recently asked about.
     *
     * A single slot rather than a full cache: the checker asks four separate questions about one meeting before it
     * moves on, and the set for one meeting spans the whole ledger up to that point, so keeping every meeting's set
     * would grow without bound.
     *
     * @var array<string, InstallationModel>|null
     */
    private ?array $installations = null;

    private ?string $installationsFor = null;

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
        $key = $meeting->getType()->value . '-' . $meeting->getNumber();

        if (
            null !== $this->installations
            && $key === $this->installationsFor
        ) {
            return $this->installations;
        }

        $createdMembers = $this->installationRepository->getAllInstallationsInstalled($meeting);
        $deletedMembers = $this->installationRepository->getAllInstallationsDischarged($meeting);

        $members = [];
        foreach ($createdMembers as $cm) {
            $members[$cm->getHash()] = $cm;
        }

        foreach ($deletedMembers as $dm) {
            $hash = $dm->getInstallation()->getHash();

            if (!isset($members[$hash])) {
                continue;
            }

            unset($members[$hash]);
        }

        $this->installations = $members;
        $this->installationsFor = $key;

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
}
