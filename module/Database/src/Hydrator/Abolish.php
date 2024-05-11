<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Abrogation as AbrogationModel;
use Database\Model\SubDecision\Discharge as DischargeModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use InvalidArgumentException;

use function array_reverse;

class Abolish extends AbstractDecision
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

        // determine who to discharge
        $members = [];

        // check installations and discharges
        foreach ($data['subdecision']->getReferences() as $ref) {
            if (!($ref instanceof InstallationModel) || null !== $ref->getDischarge()) {
                continue;
            }

            $members[] = $ref;
        }

        // discharge in reverse order
        $members = array_reverse($members);

        $num = 1;

        foreach ($members as $installation) {
            $discharge = new DischargeModel();
            $discharge->setInstallation($installation);
            $discharge->setSequence($num++);
            $discharge->setDecision($object);
        }

        $abrog = new AbrogationModel();
        $abrog->setFoundation($data['subdecision']);
        $abrog->setSequence($num++);
        $abrog->setDecision($object);

        return $object;
    }
}
