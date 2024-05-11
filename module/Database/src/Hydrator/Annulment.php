<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Annulment as AnnulmentModel;
use InvalidArgumentException;

class Annulment extends AbstractDecision
{
    /**
     * Annulment hydration
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

        $annulment = new AnnulmentModel();

        $annulment->setTarget($data['fdecision']);

        $annulment->setSequence(0);
        $annulment->setDecision($object);

        return $object;
    }
}
