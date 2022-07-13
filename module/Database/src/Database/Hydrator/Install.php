<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\SubDecision\Installation;
use Database\Model\SubDecision\Discharge;

class Install extends AbstractDecision
{
    /**
     * Install hydration
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
        // - foundation
        // - installations
        // - discharges

        $foundation = $data['subdecision'];

        $num = 1;

        // first add discharges
        if (isset($data['discharges']) && !empty($data['discharges'])) {
            foreach ($data['discharges'] as $install) {
                $discharge = new Discharge();
                $discharge->setInstallation($install);
                $discharge->setNumber($num++);
                $discharge->setDecision($decision);
            }
        }

        // then add installations
        if (isset($data['installations']) && !empty($data['installations'])) {
            foreach ($data['installations'] as $install) {
                $installation = new Installation();
                $installation->setNumber($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction($install->function);
                $installation->setMember($install->member);
                $installation->setDecision($decision);
            }
        }

        return $decision;
    }
}
