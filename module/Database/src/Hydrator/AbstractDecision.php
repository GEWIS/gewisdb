<?php

namespace Database\Hydrator;

use Laminas\Hydrator\HydratorInterface;
use Database\Model\Decision as DecisionModel;

abstract class AbstractDecision implements HydratorInterface
{
    /**
     * Decision hydration
     *
     * @param array $data
     * @param DecisionModel $object
     *
     * @return DecisionModel
     *
     * @throws \InvalidArgumentException when $object is not a Decision
     */
    public function hydrate(array $data, $object): DecisionModel
    {
        if (!$object instanceof DecisionModel) {
            throw new \InvalidArgumentException("Object is not an instance of Database\Model\Decision.");
        }

        $object->setMeeting($data['meeting']);
        $object->setPoint($data['point']);
        $object->setNumber($data['decision']);

        return $object;
    }

    /**
     * Extraction.
     *
     * Not implemented.
     *
     * @param object $object
     *
     * @return array
     */
    public function extract(object $object): array
    {
        if (!$object instanceof DecisionModel) {
            throw new \InvalidArgumentException("Object is not an instance of Database\Model\Decision.");
        }

        return [];
    }
}
