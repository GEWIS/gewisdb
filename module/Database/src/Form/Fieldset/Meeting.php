<?php

namespace Database\Form\Fieldset;

use Database\Model\Meeting as MeetingModel;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;

class Meeting extends Fieldset
{
    public function __construct()
    {
        parent::__construct('meeting');

        $this->add([
            'name' => 'type',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'number',
            'type' => 'hidden',
        ]);

        // TODO: filters
    }

    /**
     * Set meeting data.
     *
     * @param MeetingModel $meeting
     */
    public function setMeetingData(MeetingModel $meeting)
    {
        $this->get('type')->setValue($meeting->getType()->value);
        $this->get('number')->setValue($meeting->getNumber());
    }
}
