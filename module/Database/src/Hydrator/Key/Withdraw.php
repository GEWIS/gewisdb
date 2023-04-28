<?php

declare(strict_types=1);

namespace Database\Hydrator\Key;

use Database\Hydrator\AbstractDecision;
use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Key\Withdrawal as KeyWithdrawal;
use DateTime;
use InvalidArgumentException;

class Withdraw extends AbstractDecision
{
    /**
     * Key withdrawal hydration
     *
     * @param DecisionModel $object
     *
     * @throws InvalidArgumentException when $decision is not a Decision.
     */
    public function hydrate(
        array $data,
        $object,
    ): DecisionModel {
        $decision = parent::hydrate($data, $object);

        // data contains:
        // - meeting
        // - granting decision
        // - withdrawn from date

        $release = new KeyWithdrawal();

        $release->setNumber(1);
        $release->setGranting($data['subdecision']);
        $release->setWithdrawnOn(new DateTime($data['withdrawOn']));

        $release->setDecision($decision);

        return $decision;
    }
}
