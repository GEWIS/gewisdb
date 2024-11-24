<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\Enums\InstallationFunctions;
use Database\Model\SubDecision\Discharge as DischargeModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use Database\Model\SubDecision\Reappointment as ReappointmentModel;
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

        // first do reappointments
        if (!empty($data['reappointments'])) {
            foreach ($data['reappointments'] as $install) {
                $reappointment = new ReappointmentModel();
                $reappointment->setInstallation($install);
                $reappointment->setSequence($num++);
                $reappointment->setDecision($decision);
            }
        }

        // then add discharges
        if (!empty($data['discharges'])) {
            foreach ($data['discharges'] as $install) {
                $discharge = new DischargeModel();
                $discharge->setInstallation($install);
                $discharge->setSequence($num++);
                $discharge->setDecision($decision);
            }
        }

        // finally add installations
        if (!empty($data['installations'])) {
            foreach ($data['installations'] as $install) {
                if (!($install['function'] instanceof InstallationFunctions)) {
                    $install['function'] = InstallationFunctions::from($install['function']);
                }

                $installation = new InstallationModel();
                $installation->setSequence($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction($install['function']);
                $installation->setMember($install['member']);
                $installation->setDecision($decision);
            }
        }

        return $decision;
    }
}
