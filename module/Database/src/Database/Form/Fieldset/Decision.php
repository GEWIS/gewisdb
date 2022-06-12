<?php

namespace Database\Form\Fieldset;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;

class Decision extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('fdecision');

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
            'name' => 'number',
            'type' => 'hidden'
        ));
    }

    /**
     * Specification for input filters.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'meeting_type' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'in_array',
                        'options' => array(
                            'haystack' => MeetingModel::getTypes()
                        )
                    )
                )
            ),
            'meeting_number' => array(
                'required' => true,
                'validators' => array(
                    array('name' => 'digits')
                )
            ),
            'point' => array(
                'required' => true,
                'validators' => array(
                    array('name' => 'digits')
                )
            ),
            'number' => array(
                'required' => true,
                'validators' => array(
                    array('name' => 'digits')
                )
            ),
        );
    }
}
