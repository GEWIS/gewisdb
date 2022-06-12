<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\SubDecision\Installation;
use Database\Model\SubDecision\Discharge;
use Database\Model\SubDecision\Abrogation;

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

        // determine who to discharge
        $members = array();

        // check installations and discharges
        foreach ($data['subdecision']->getReferences() as $ref) {
            if ($ref instanceof Installation && null === $ref->getDischarge()) {
                $members[] = $ref;
            }
        }

        // discharge in reverse order
        $members = array_reverse($members);

        $num = 1;

        foreach ($members as $installation) {
            $discharge = new Discharge();
            $discharge->setInstallation($installation);
            $discharge->setNumber($num++);
            $discharge->setDecision($object);
        }

        $abrog = new Abrogation();
        $abrog->setFoundation($data['subdecision']);
        $abrog->setNumber($num++);
        $abrog->setDecision($object);

        return $object;
    }
}
