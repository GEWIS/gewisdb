<?php

namespace Database\Form;

use Zend\Form\Form;
use Database\Model\Meeting;

abstract class AbstractDecision extends Form
{
    public function __construct(Fieldset\Meeting $meeting)
    {
        parent::__construct();

        $meeting->setName('meeting');
        $this->add($meeting);

        $this->add([
            'name' => 'point',
            'type' => 'hidden'
        ]);

        $this->add([
            'name' => 'decision',
            'type' => 'hidden'
        ]);

        // TODO: filters
    }

    /**
     * Set data for the decision.
     *
     * @param Meeting $meeting
     * @param int $point
     * @param int $decision
     */
    public function setDecisionData(Meeting $meeting, $point, $decision)
    {
        $this->get('meeting')->setMeetingData($meeting);
        $this->get('point')->setValue($point);
        $this->get('decision')->setValue($decision);
    }
}
