<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class Install extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting, Fieldset\Member $member)
    {
        parent::__construct($meeting);

        $this->add(clone $member);

        $this->add([
            'name' => 'function',
            'type' => 'text',
            'options' => [
                'label' => 'Functie',
            ]
        ]);

        $this->add([
            'name' => 'date',
            'type' => 'date',
            'options' => [
                'label' => 'Van kracht per',
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Installeer bestuurder'
            ]
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return [
            'function' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 32
                        ]
                    ]
                ]
            ],
            'date' => [
                'required' => true
            ]
        ];
    }
}
