<?php

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\{
    Installation as InstallationModel,
    Discharge as DischargeModel,
    Abrogation as AbrogationModel,
};

class Abolish extends AbstractDecision
{
    /**
     * abolish hydration
     *
     * @param array $data
     * @param DecisionModel $object
     *
     * @return DecisionModel
     *
     * @throws \InvalidArgumentException when $object is not a Decision
     */
    public function hydrate(array $data, $object): DecisionModel
    {
        $object = parent::hydrate($data, $object);

        // determine who to discharge
        $members = [];

        // check installations and discharges
        foreach ($data['subdecision']->getReferences() as $ref) {
            if ($ref instanceof InstallationModel && null === $ref->getDischarge()) {
                $members[] = $ref;
            }
        }

        // discharge in reverse order
        $members = array_reverse($members);

        $num = 1;

        foreach ($members as $installation) {
            $discharge = new DischargeModel();
            $discharge->setInstallation($installation);
            $discharge->setNumber($num++);
            $discharge->setDecision($object);
        }

        $abrog = new AbrogationModel();
        $abrog->setFoundation($data['subdecision']);
        $abrog->setNumber($num++);
        $abrog->setDecision($object);

        return $object;
    }
}
