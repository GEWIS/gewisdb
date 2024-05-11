<?php

declare(strict_types=1);

namespace Database\Hydrator\Board;

use Database\Hydrator\AbstractDecision;
use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Board\Installation as BoardInstall;
use DateTime;
use InvalidArgumentException;

class Install extends AbstractDecision
{
    /**
     * Board install hydration
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
        // - member
        // - function

        $install = new BoardInstall();

        $install->setSequence(1);
        $install->setMember($data['member']);
        $install->setFunction($data['function']);
        $install->setDate(new DateTime($data['date']));

        $install->setDecision($decision);

        return $decision;
    }
}
