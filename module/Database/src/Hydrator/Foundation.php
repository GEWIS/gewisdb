<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Application\Model\Enums\OrganTypes;
use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\{
    Foundation as FoundationModel,
    Installation as InstallationModel,
};

class Foundation extends AbstractDecision
{
    /**
     * Budget hydration
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

        $foundation = new FoundationModel();

        $foundation->setNumber(1);
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
            // if an installation has a different function than 'member'
            // also add a member installation
            if (
                'Lid' !== $install['function']
                && 'Inactief Lid' !== $install['function']
            ) {
                $installation = new InstallationModel();
                $installation->setNumber($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction('Lid');
                $installation->setMember($install['member']);
                $installation->setDecision($decision);
            }

            $installation = new InstallationModel();
            $installation->setNumber($num++);
            $installation->setFoundation($foundation);
            $installation->setFunction($install['function']);
            $installation->setMember($install['member']);
            $installation->setDecision($decision);
        }

        return $decision;
    }
}
