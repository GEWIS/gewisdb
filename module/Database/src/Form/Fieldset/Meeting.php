<?php

declare(strict_types=1);

namespace Database\Form\Fieldset;

use Database\Model\Meeting as MeetingModel;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Fieldset;

class Meeting extends Fieldset
{
    public function __construct()
    {
        parent::__construct('meeting');

        $this->add([
            'name' => 'type',
            'type' => Hidden::class,
        ]);

        $this->add([
            'name' => 'number',
            'type' => Hidden::class,
        ]);

        // TODO: filters
    }

    /**
     * Set meeting data.
     *
     * @param MeetingModel $meeting
     */
    public function setMeetingData(MeetingModel $meeting): void
    {
        $this->get('type')->setValue($meeting->getType()->value);
        $this->get('number')->setValue($meeting->getNumber());
    }
}
