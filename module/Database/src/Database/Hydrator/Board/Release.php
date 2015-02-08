<?php

namespace Database\Hydrator\Board;

use Database\Model\SubDecision\Board\Release as BoardRelease;
use Database\Model\Decision;
use Database\Hydrator\AbstractDecision;

class Release extends AbstractDecision
{

    /**
     * Board release hydration
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

        $release = new BoardRelease();

        $release->setNumber(1);
        $release->setInstallation($data['subdecision']);
        $release->setDate(new \DateTime($data['date']));

        $release->setDecision($decision);

        return $decision;
    }
}
