<?php

namespace Database\Hydrator\Board;

use Database\Model\SubDecision\Board\Discharge as BoardDischarge;
use Database\Model\Decision;
use Database\Hydrator\AbstractDecision;

class Discharge extends AbstractDecision
{
    /**
     * Board discharge hydration
     *
     * @param array $data
     * @param Decision $decision
     *
     * @return Decision
     *
     * @throws \InvalidArgumentException when $decision is not a Decision
     */
    public function hydrate(array $data, $decision)
    {
        $decision = parent::hydrate($data, $decision);

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
