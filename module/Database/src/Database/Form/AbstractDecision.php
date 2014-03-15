<?php

namespace Database\Form;

use Zend\Form\Form;

use Database\Model\Meeting;

abstract class AbstractDecision extends Form
{

    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'meeting_type',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'meeting_number',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'point',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'decision',
            'type' => 'hidden'
        ));

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
        $this->get('meeting_type')->setValue($meeting->getType());
        $this->get('meeting_number')->setValue($meeting->getNumber());
        $this->get('point')->setValue($point);
        $this->get('decision')->setValue($decision);
    }
}
