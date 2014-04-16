<?php

namespace Database\Hydrator;

use Database\Model\Decision;

class Abolish extends AbstractDecision
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

        var_dump($data);

        return $object;
    }
}
