<?php

declare(strict_types=1);

namespace Database\Hydrator\Board;

use Database\Model\SubDecision\Board\Release as BoardRelease;
use Database\Model\Decision as DecisionModel;
use Database\Hydrator\AbstractDecision;

class Release extends AbstractDecision
{
    /**
     * Board release hydration
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

        $release = new BoardRelease();

        $release->setNumber(1);
        $release->setInstallation($data['subdecision']);
        $release->setDate(new \DateTime($data['date']));

        $release->setDecision($decision);

        return $decision;
    }
}
