<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision\Abrogation as AbrogationModel;
use App\Entity\Database\SubDecision\Foundation as FoundationModel;
use App\Repository\Checker\OrganRepository;

use function array_keys;
use function array_map;
use function in_array;

class Organ
{
    /**
     * The organs of the meeting most recently asked about, for the same reason as in {@see Installation}.
     *
     * @var array<string, FoundationModel>|null
     */
    private ?array $foundations = null;

    private ?string $foundationsFor = null;

    public function __construct(private readonly OrganRepository $organRepository)
    {
    }

    /**
     * Get the hashes of all the organs after $meeting
     *
     * @return string[]
     */
    public function getAllOrgans(MeetingModel $meeting): array
    {
        return array_keys($this->getAllOrganFoundations($meeting));
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
        $key = $meeting->getType()->value . '-' . $meeting->getNumber();

        if (
            null !== $this->foundations
            && $key === $this->foundationsFor
        ) {
            return $this->foundations;
        }

        $abrogated = array_map(
            static function (AbrogationModel $organ): string {
                return $organ->getFoundation()->getHash();
            },
            $this->organRepository->getAllOrganAbrogations($meeting),
        );

        $organs = [];

        foreach ($this->organRepository->getAllOrganFoundations($meeting) as $foundation) {
            $hash = $foundation->getHash();

            if (
                in_array(
                    $hash,
                    $abrogated,
                    true,
                )
            ) {
                continue;
            }

            $organs[$hash] = $foundation;
        }

        $this->foundations = $organs;
        $this->foundationsFor = $key;

        return $organs;
    }

    /**
     * @return FoundationModel[]
     */
    public function getOrgansCreatedAtMeeting(MeetingModel $meeting): array
    {
        return $this->organRepository->getOrgansCreatedAtMeeting($meeting);
    }
}
