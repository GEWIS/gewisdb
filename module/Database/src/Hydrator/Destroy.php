<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Destroy as DestroyModel;
use InvalidArgumentException;

class Destroy extends AbstractDecision
{
    /**
     * abolish hydration
     *
     * @param DecisionModel $object
     *
     * @throws InvalidArgumentException when $object is not a Decision.
     */
    public function hydrate(
        array $data,
        $object,
    ): DecisionModel {
        $object = parent::hydrate($data, $object);

        $destroy = new DestroyModel();

        $destroy->setTarget($data['fdecision']);

        $destroy->setNumber(0);
        $destroy->setDecision($object);

        return $object;
    }
}
