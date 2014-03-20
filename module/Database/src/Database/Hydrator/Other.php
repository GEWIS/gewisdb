<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\SubDecision\Other as OtherDecision;

class Other extends AbstractDecision
{

    /**
     * Budget hydration
     *
     * @param array $data
     * @param SubDecision $object
     *
     * @return SubDecision
     *
     * @throws \InvalidArgumentException when $object is not a SubDecision
     */
    public function hydrate(array $data, $object)
    {
        $object = parent::hydrate($data, $object);

        $subdecision = new OtherDecision();

        $subdecision->setNumber(1);
        $subdecision->setContent($data['content']);

        $subdecision->setDecision($object);

        return $object;
    }
}
