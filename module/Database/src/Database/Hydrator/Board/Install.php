<?php

namespace Database\Hydrator\Board;

use Database\Model\SubDecision\Board\Installation as BoardInstall;
use Database\Model\Decision;
use Database\Hydrator\AbstractDecision;

class Install extends AbstractDecision
{
    /**
     * Board install hydration
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
        // - member
        // - function

        $install = new BoardInstall();

        $install->setNumber(1);
        $install->setMember($data['member']);
        $install->setFunction($data['function']);
        $install->setDate(new \DateTime($data['date']));

        $install->setDecision($decision);

        return $decision;
    }
}
