<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset;
use Database\Service\Meeting as MeetingService;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class Release extends AbstractDecision
    implements InputFilterProviderInterface
{

    protected $service;

    public function __construct(Fieldset\Meeting $meeting, Fieldset\SubDecision $installation, MeetingService $service)
    {
        parent::__construct($meeting);
        $this->service = $service;

        $this->add(clone $installation);

        $this->add(array(
            'name' => 'date',
            'type' => 'date',
            'options' => array(
                'label' => 'Van kracht per',
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Dechargeer bestuurder'
            )
        ));
    }

    /**
     * Get the meeting service.
     *
     * @return MeetingService
     */
    public function getMeetingService()
    {
        return $this->service;
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'date' => array(
                'required' => true
            )
        );
    }
}
