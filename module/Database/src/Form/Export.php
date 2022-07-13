<?php

namespace Database\Form;

use Database\Service\Meeting as MeetingService;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class Export extends Form implements InputFilterProviderInterface
{
    public function __construct(MeetingService $service)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'meetings',
            'type' => 'select',
            'attributes' => array(
                'multiple' => 'multiple'
            ),
            'options' => array(
                'label' => 'Vergaderingen',
                'value_options' => $this->getValueOptions($service)
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Exporteer'
            )
        ));
    }

    protected function getValueOptions(MeetingService $service)
    {
        $options = array();

        foreach ($service->getAllMeetings() as $meeting) {
            $meeting = $meeting[0];
            $id = $meeting->getType() . '-' . $meeting->getNumber();
            $options[$id] = strtoupper($meeting->getType()) . ' ' . $meeting->getNumber()
                          . '   (' . $meeting->getDate()->format('j F Y') . ')';
        }

        return $options;
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array(
        );
    }
}
