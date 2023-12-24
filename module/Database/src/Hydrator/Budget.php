<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Budget as BudgetModel;
use Database\Model\SubDecision\Reckoning as ReckoningModel;
use DateTime;
use InvalidArgumentException;

use function boolval;

class Budget extends AbstractDecision
{
    /**
     * Budget hydration
     *
     * @param DecisionModel $object
     *
     * @throws InvalidArgumentException when $object is not a SubDecision.
     */
    public function hydrate(
        array $data,
        $object,
    ): DecisionModel {
        $object = parent::hydrate($data, $object);

        if ('budget' === $data['type']) {
            $subdecision = new BudgetModel();
        } else {
            $subdecision = new ReckoningModel();
        }

        $subdecision->setNumber(1);

        $date = new DateTime($data['date']);
        $subdecision->setDate($date);

        $subdecision->setName($data['name']);
        $subdecision->setMember($data['author']);
        $subdecision->setVersion($data['version']);
        $subdecision->setApproval(boolval($data['approve']));
        $subdecision->setChanges(boolval($data['changes']));

        $subdecision->setDecision($object);

        return $object;
    }
}
