<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Application\Model\Enums\OrganTypes;
use Database\Model\Decision as DecisionModel;
use Database\Model\Enums\InstallationFunctions;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use InvalidArgumentException;
use Override;

use function in_array;
use function sprintf;

class Foundation extends AbstractDecision
{
    /**
     * Budget hydration
     *
     * @param DecisionModel $object
     *
     * @throws InvalidArgumentException when $decision is not a Decision.
     */
    #[Override]
    public function hydrate(
        array $data,
        $object,
    ): DecisionModel {
        $decision = parent::hydrate($data, $object);

        $foundation = new FoundationModel();

        $foundation->setSequence(1);

        if (!($data['type'] instanceof OrganTypes)) {
            $data['type'] = OrganTypes::from($data['type']);
        }

        $foundation->setOrganType($data['type']);
        $foundation->setDecision($decision);

        if (OrganTypes::SC !== $foundation->getOrganType()) {
            $foundation->setName($data['name']);
            $foundation->setAbbr($data['abbr']);
        } else {
            $foundation->setName(sprintf(
                'Stemcommissie voor %s van de %de ALV',
                $data['name'],
                $foundation->getMeetingNumber(),
            ));
            $foundation->setAbbr(sprintf(
                'SC%d-%s',
                $foundation->getMeetingNumber(),
                $data['abbr'],
            ));
            $foundation->setPurpose($data['name']);
        }

        $num = 2;

        $addedMembers = [];

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
                && !in_array($install['member']->getLidnr(), $addedMembers, true)
            ) {
                $installation = new InstallationModel();
                $installation->setSequence($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction(InstallationFunctions::Member);
                $installation->setMember($install['member']);
                $installation->setDecision($decision);
                $addedMembers[] = $install['member']->getLidnr();
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
