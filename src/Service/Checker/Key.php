<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision\Key\Granting as KeyGrantingModel;
use App\Entity\Database\SubDecision\Key\Withdrawal as KeyWithdrawalModel;
use App\Repository\Checker\KeyRepository;

class Key
{
    public function __construct(private readonly KeyRepository $keyRepository)
    {
    }

    /**
     * @return array<array-key, KeyGrantingModel>
     */
    public function getKeysGrantedDuringMeeting(MeetingModel $meeting): array
    {
        return $this->keyRepository->findKeysGrantedDuringMeeting($meeting);
    }

    /**
     * @return array<array-key, KeyWithdrawalModel>
     */
    public function getKeysWithdrawnDuringMeeting(MeetingModel $meeting): array
    {
        return $this->keyRepository->findKeysWithdrawnDuringMeeting($meeting);
    }
}
