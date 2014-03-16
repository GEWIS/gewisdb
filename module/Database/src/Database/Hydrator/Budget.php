<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\SubDecision\Budget as BudgetDecision;
use Database\Model\SubDecision\Reckoning as ReckoningDecision;

class Budget extends AbstractDecision
{

    const BUDGET_TEMPLATE = 'De %TYPE% %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVE%%CHANGES%.';

    /**
     * Budget hydration
     *
     * @param array $data
     * @param SubDecision $object
     *
     * @return SubDecision
     *
     * @throws \InvalidArgumentException when $object is not a SubDecision
     */
    public function hydrate(array $data, $object)
    {
        $object = parent::hydrate($data, $object);
        var_dump($data);

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
        $subdecision->setApproval($data['approve'] == 'aprove');
        $subdecision->setChanges($data['changes'] == 'yes');

        $subdecision->setDecision($object);

        return $object;
    }
}
