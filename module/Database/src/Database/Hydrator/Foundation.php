<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\SubDecision\Foundation as FoundationDecision;
use Database\Model\SubDecision\Installation as InstallationDecision;

class Foundation extends AbstractDecision
{

    /**
     * Budget hydration
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

        $foundation = new FoundationDecision();

        $foundation->setNumber(1);
        $foundation->setAbbr($data['abbr']);
        $foundation->setName($data['name']);
        $foundation->setOrganType($data['type']);
        $foundation->setDecision($decision);

        $num = 2;

        // create installations
        foreach ($data['members'] as $install) {
            // if an installation has a different function than 'member'
            // also add a member installation
            if ($install->function != 'Lid') {
                $installation = new InstallationDecision();
                $installation->setNumber($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction('Lid');
                $installation->setMember($install->member);
                $installation->setDecision($decision);
            }
            $installation = new InstallationDecision();
            $installation->setNumber($num++);
            $installation->setFoundation($foundation);
            $installation->setFunction($install->function);
            $installation->setMember($install->member);
            $installation->setDecision($decision);
        }

        return $decision;
    }
}
