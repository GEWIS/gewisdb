<?php

declare(strict_types=1);

namespace Database\Hydrator\Board;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Board\Discharge as BoardDischarge;
use Database\Hydrator\AbstractDecision;

class Discharge extends AbstractDecision
{
    /**
     * Board discharge hydration
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
        // - installation decision
        // - date

        $discharge = new BoardDischarge();

        $discharge->setNumber(1);
        $discharge->setInstallation($data['subdecision']);

        $discharge->setDecision($decision);

        return $decision;
    }
}
