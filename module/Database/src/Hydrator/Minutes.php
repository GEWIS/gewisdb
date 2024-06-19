<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Minutes as MinutesModel;
use InvalidArgumentException;

use function boolval;

class Minutes extends AbstractDecision
{
    /**
     * Minutes hydration
     *
     * @param DecisionModel $object
     *
     * @throws InvalidArgumentException when $object is not a Decision.
     */
    public function hydrate(
        array $data,
        $object,
    ): DecisionModel {
        $object = parent::hydrate($data, $object);

        $subdecision = new MinutesModel();

        $subdecision->setTarget($data['fmeeting']);

        $subdecision->setMember($data['author']);
        $subdecision->setApproval(boolval($data['approve']));
        $subdecision->setChanges(boolval($data['changes']));

        $subdecision->setDecision($object);
        $subdecision->setSequence(1);

        return $object;
    }
}
