<?php

declare(strict_types=1);

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\SubDecision\Key\Withdrawal as KeyWithdrawalModel;

use function sprintf;

/**
 * Error for when a key code is withdrawn when the original granting already expired.
 *
 * @extends Error<KeyWithdrawalModel>
 */
class KeyWithdrawnPastOriginalGranting extends Error
{
    public function __construct(KeyWithdrawalModel $withdrawal)
    {
        parent::__construct(
            $withdrawal->getDecision()->getMeeting(),
            $withdrawal,
        );
    }

    /**
     * Get the withdrawal.
     */
    private function getWithdrawal(): KeyWithdrawalModel
    {
        return $this->getSubDecision();
    }

    public function asText(): string
    {
        return sprintf(
            'Key code of %s withdrawn per %s, this is after the original expiration %s.',
            $this->getWithdrawal()->getGranting()->getGrantee()->getFullName(),
            $this->getWithdrawal()->getWithdrawnOn()->format('Y-m-d'),
            $this->getWithdrawal()->getGranting()->getUntil()->format('Y-m-d'),
        );
    }
}
