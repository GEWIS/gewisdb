<?php

namespace Database\Hydrator;

use Zend\Stdlib\Hydrator\HydratorInterface;
use Database\Model\Decision;
use Database\Model\SubDecision;
use Database\Model\Meeting;

abstract class AbstractDecision implements HydratorInterface
{
    /**
     * Decision hydration
     *
     * @param array $data
     * @param Decision $object
     *
     * @return Decision
     *
     * @throws \InvalidArgumentException when $object is not a Decision
     */
    public function hydrate(array $data, $object)
    {
        if (!$object instanceof Decision) {
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
     * @return array
     */
    public function extract($object)
    {
        if (!$object instanceof Decision) {
            throw new \InvalidArgumentException("Object is not an instance of Database\Model\Decision.");
        }
        return [];
    }
}
