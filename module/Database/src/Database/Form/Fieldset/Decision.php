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

        $this->add([
            'name' => 'meeting_type',
            'type' => 'hidden'
        ]);

        $this->add([
            'name' => 'meeting_number',
            'type' => 'hidden'
        ]);

        $this->add([
            'name' => 'point',
            'type' => 'hidden'
        ]);

        $this->add([
            'name' => 'number',
            'type' => 'hidden'
        ]);
    }

    /**
     * Specification for input filters.
     */
    public function getInputFilterSpecification()
    {
        return [
            'meeting_type' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'in_array',
                        'options' => [
                            'haystack' => MeetingModel::getTypes()
                        ]
                    ]
                ]
            ],
            'meeting_number' => [
                'required' => true,
                'validators' => [
                    ['name' => 'digits']
                ]
            ],
            'point' => [
                'required' => true,
                'validators' => [
                    ['name' => 'digits']
                ]
            ],
            'number' => [
                'required' => true,
                'validators' => [
                    ['name' => 'digits']
                ]
            ],
        ];
    }
}
