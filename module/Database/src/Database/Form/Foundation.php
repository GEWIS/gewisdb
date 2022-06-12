<?php

namespace Database\Form;

use Database\Form\Fieldset\CollectionWithErrors;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class Foundation extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting, Fieldset\MemberFunction $function)
    {
        parent::__construct($meeting);

        $this->add([
            'name' => 'type',
            'type' => 'radio',
            'options' => [
                'label' => 'Type',
                'value_options' => [
                    'committee' => 'Commissie',
                    'avc' => 'AV-Commissie',
                    'avw' => 'AV-Werkgroep',
                    'kkk' => 'KKK',
                    'fraternity' => 'Dispuut',
                    'rva' => 'RvA'
                ]
            ]
        ]);

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Naam',
            ]
        ]);

        $this->add([
            'name' => 'abbr',
            'type' => 'text',
            'options' => [
                'label' => 'Afkorting'
            ]
        ]);

        // Is this possible with a factory?
        $members = new CollectionWithErrors();
        $members->setName('members');
        $members->setOptions([
            'label' => 'Members',
            'count' => 2,
            'should_create_template' => true,
            'target_element' => $function
        ]);
        $this->add($members);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Richt op'
            ]
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return [
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 128
                        ]
                    ]
                ]
            ],

            'abbr' => [
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

            'members' => [
                'continue_if_empty' => true,
                'validators' => [
                    [
                        'name' => 'notEmpty',
                    ]
                ]
            ]
        ];
    }
}
