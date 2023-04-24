<?php

declare(strict_types=1);

namespace Database\Hydrator\Key;

use Database\Hydrator\AbstractDecision;
use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Key\Granting as KeyGranting;
use DateTime;

class Grant extends AbstractDecision
{
    /**
     * Key granting hydration
     *
     * @param array $data
     * @param DecisionModel $object
     *
     * @return DecisionModel
     *
     * @throws \InvalidArgumentException when $decision is not a Decision
     */
    public function hydrate(array $data, $object): DecisionModel
    {
        $decision = parent::hydrate($data, $object);

        // data contains:
        // - meeting
        // - grantee (member)
        // - until

        $install = new KeyGranting();

        $install->setNumber(1);
        $install->setGrantee($data['grantee']);
        $install->setUntil(new DateTime($data['until']));

        $install->setDecision($decision);

        return $decision;
    }
}
