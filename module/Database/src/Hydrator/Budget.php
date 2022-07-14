<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Budget as BudgetDecision;
use Database\Model\SubDecision\Reckoning as ReckoningDecision;

class Budget extends AbstractDecision
{
    /**
     * Budget hydration
     *
     * @param array $data
     * @param DecisionModel $object
     *
     * @return DecisionModel
     *
     * @throws \InvalidArgumentException when $object is not a SubDecision
     */
    public function hydrate(array $data, $object): DecisionModel
    {
        $object = parent::hydrate($data, $object);

        if ($data['type'] == 'budget') {
            $subdecision = new BudgetDecision();
        } else {
            $subdecision = new ReckoningDecision();
        }

        $subdecision->setNumber(1);

        $date = new \DateTime($data['date']);
        $subdecision->setDate($date);

        $subdecision->setName($data['name']);
        $subdecision->setAuthor($data['author']);
        $subdecision->setVersion($data['version']);
        $subdecision->setApproval($data['approve']);
        $subdecision->setChanges($data['changes']);

        $subdecision->setDecision($object);

        return $object;
    }
}
