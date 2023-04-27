<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Discharge as DischargeModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use InvalidArgumentException;

class Install extends AbstractDecision
{
    /**
     * Install hydration
     *
     * @param DecisionModel $object
     *
     * @throws InvalidArgumentException when $decision is not a Decision.
     */
    public function hydrate(
        array $data,
        $object,
    ): DecisionModel {
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
                $discharge = new DischargeModel();
                $discharge->setInstallation($install);
                $discharge->setNumber($num++);
                $discharge->setDecision($decision);
            }
        }

        // then add installations
        if (isset($data['installations']) && !empty($data['installations'])) {
            foreach ($data['installations'] as $install) {
                $installation = new InstallationModel();
                $installation->setNumber($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction($install['function']);
                $installation->setMember($install['member']);
                $installation->setDecision($decision);
            }
        }

        return $decision;
    }
}
