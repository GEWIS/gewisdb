<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\{
    Budget as BudgetModel,
    Reckoning as ReckoningModel,
};
use DateTime;

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
            $subdecision = new BudgetModel();
        } else {
            $subdecision = new ReckoningModel();
        }

        $subdecision->setNumber(1);

        $date = new DateTime($data['date']);
        $subdecision->setDate($date);

        $subdecision->setName($data['name']);
        $subdecision->setAuthor($data['author']);
        $subdecision->setVersion($data['version']);
        $subdecision->setApproval(boolval($data['approve']));
        $subdecision->setChanges(boolval($data['changes']));

        $subdecision->setDecision($object);

        return $object;
    }
}
