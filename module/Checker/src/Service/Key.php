<?php

namespace Checker\Service;

use Checker\Mapper\Key as KeyMapper;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Key\{
    Granting as KeyGrantingModel,
    Withdrawal as KeyWithdrawalModel,
};

class Key
{
    public function __construct(private readonly KeyMapper $keyMapper)
    {
    }

    /**
     * @return array<array-key, KeyGrantingModel>
     */
    public function getKeysGrantedDuringMeeting(MeetingModel $meeting): array
    {
        return $this->keyMapper->findKeysGrantedDuringMeeting($meeting);
    }

    /**
     * @return array<array-key, KeyWithdrawalModel>
     */
    public function getKeysWithdrawnDuringMeeting(MeetingModel $meeting): array
    {
        return $this->keyMapper->findKeysWithdrawnDuringMeeting($meeting);
    }
}
