<?php

namespace Database\Form;

use Database\Mapper\Meeting as MeetingMapper;
use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

class Export extends Form implements InputFilterProviderInterface
{
    public function __construct(MeetingMapper $meetingMapper)
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
                'value_options' => $this->getValueOptions($meetingMapper)
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

    protected function getValueOptions(MeetingMapper $meetingMapper)
    {
        $options = array();

        foreach ($meetingMapper->findAll() as $meeting) {
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
    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
