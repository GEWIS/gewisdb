<?php

namespace Database\Hydrator;

use Zend\Stdlib\Hydrator\HydratorInterface;
use Database\Model\Decision;
use Database\Model\SubDecision;
use Database\Model\Meeting;

abstract class AbstractDecision implements HydratorInterface
{

    /**
     * Meeting hydrator.
     *
     * @var HydratorInterface
     */
    protected $meetingHydrator;

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
        $meeting = $this->hydrateMeeting($data['meeting_type'], $data['meeting_number']);
        $object->setMeeting($meeting);
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
        return array();
    }

    /**
     * Hydrate a meeting.
     *
     * @param int $type
     * @param int $number
     *
     * @return Meeting
     */
    protected function hydrateMeeting($type, $number)
    {
        return $this->getMeetingHydrator()
            ->hydrate(array(
                'type' => $type,
                'number' => $number
            ), new Meeting());
    }

    /**
     * Set meeting hydrator.
     *
     * @param HydratorInterface $meetingHydrator
     */
    public function setMeetingHydrator(HydratorInterface $meetingHydrator)
    {
        $this->meetingHydrator = $meetingHydrator;
    }

    /**
     * Get meeting hydrator.
     *
     * @return HydratorInterface
     */
    public function getMeetingHydrator()
    {
        return $this->meetingHydrator;
    }
}
