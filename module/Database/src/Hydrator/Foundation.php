<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Application\Model\Enums\OrganTypes;
use Database\Model\Decision as DecisionModel;
use Database\Model\Enums\InstallationFunctions;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use InvalidArgumentException;

class Foundation extends AbstractDecision
{
    /**
     * Budget hydration
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

        $foundation = new FoundationModel();

        $foundation->setSequence(1);
        $foundation->setAbbr($data['abbr']);
        $foundation->setName($data['name']);

        if (!($data['type'] instanceof OrganTypes)) {
            $data['type'] = OrganTypes::from($data['type']);
        }

        $foundation->setOrganType($data['type']);
        $foundation->setDecision($decision);

        $num = 2;

        // create installations
        foreach ($data['members'] as $install) {
            if (!($install['function'] instanceof InstallationFunctions)) {
                $install['function'] = InstallationFunctions::from($install['function']);
            }

            // if an installation has a different function than 'member'
            // also add a member installation
            if (
                InstallationFunctions::Member !== $install['function']
                && InstallationFunctions::InactiveMember !== $install['function']
            ) {
                $installation = new InstallationModel();
                $installation->setSequence($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction(InstallationFunctions::Member);
                $installation->setMember($install['member']);
                $installation->setDecision($decision);
            }

            $installation = new InstallationModel();
            $installation->setSequence($num++);
            $installation->setFoundation($foundation);
            $installation->setFunction($install['function']);
            $installation->setMember($install['member']);
            $installation->setDecision($decision);
        }

        return $decision;
    }
}
