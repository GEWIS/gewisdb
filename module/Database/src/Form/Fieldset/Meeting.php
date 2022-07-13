<?php

namespace Database\Form\Fieldset;

use Database\Model\Meeting as MeetingModel;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;

class Meeting extends Fieldset
{
    public function __construct()
    {
        parent::__construct('meeting');

        $this->add(array(
            'name' => 'type',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'number',
            'type' => 'hidden'
        ));

        // TODO: filters
    }

    /**
     * Set meeting data.
     *
     * @param MeetingModel $meeting
     */
    public function setMeetingData(MeetingModel $meeting)
    {
        $this->get('type')->setValue($meeting->getType());
        $this->get('number')->setValue($meeting->getNumber());
    }
}
