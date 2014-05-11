<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\SubDecision\Installation as InstallationDecision;

class Install extends AbstractDecision
{

    /**
     * Budget hydration
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

        var_dump($data);

        return $decision;
    }
}
