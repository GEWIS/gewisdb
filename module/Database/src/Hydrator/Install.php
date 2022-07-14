<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Installation;
use Database\Model\SubDecision\Discharge;

class Install extends AbstractDecision
{
    /**
     * Install hydration
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
