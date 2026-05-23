<?php

declare(strict_types=1);

namespace Database\Hydrator;

use Database\Model\Decision as DecisionModel;
use Database\Model\SubDecision\Other as OtherModel;
use InvalidArgumentException;
use Override;

class Other extends AbstractDecision
{
    /**
     * Budget hydration
     *
     * @param DecisionModel $object
     *
     * @throws InvalidArgumentException when $object is not a SubDecision.
     */
    #[Override]
    public function hydrate(
        array $data,
        $object,
    ): DecisionModel {
        $object = parent::hydrate($data, $object);

        $subdecision = new OtherModel();

        $subdecision->setSequence(1);
        $subdecision->setContent($data['content']);

        $subdecision->setDecision($object);

        return $object;
    }
}
