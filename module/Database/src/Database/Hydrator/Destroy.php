<?php

namespace Database\Hydrator;

use Database\Model\Decision;

use Database\Model\SubDecision\Destroy as DestroyDecision;

class Destroy extends AbstractDecision
{

    /**
     * abolish hydration
     *
     * @param array $data
     * @param Decision $object
     *
     * @return Decision
     *
     * @throws \InvalidArgumentException when $object is not a Decision
     */
    public function hydrate(array $data, $object)
    {
        $object = parent::hydrate($data, $object);

        $destroy = new DestroyDecision();

        $destroy->setTarget($data['fdecision']);

        $destroy->setNumber(0);
        $destroy->setDecision($object);

        return $object;
    }
}
