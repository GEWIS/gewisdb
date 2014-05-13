<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

use Database\Service\Meeting as MeetingService;

class Export extends Form
    implements InputFilterProviderInterface
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
    }

    protected function getValueOptions(MeetingService $service)
    {
        $options = array();

        foreach ($service->getAllMeetings() as $meeting) {
            $meeting = $meeting[0];
            $id = $meeting->getType() . '-' . $meeting->getNumber();
            $options[$id] = strtoupper($meeting->getType()) . ' ' . $meeting->getNumber();
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
