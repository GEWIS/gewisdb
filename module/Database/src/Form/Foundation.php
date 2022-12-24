<?php

namespace Database\Form;

use Database\Form\Fieldset\{
    CollectionWithErrors,
    Meeting as MeetingFieldset,
    MemberFunction as MemberFunctionFieldset,
};
use Laminas\Form\Element\{
    Radio,
    Submit,
    Text,
};
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    NotEmpty,
    StringLength,
};

class Foundation extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        MeetingFieldset $meeting,
        MemberFunctionFieldset $function,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'type',
            'type' => Radio::class,
            'options' => [
                'label' => 'Type',
                'value_options' => [
                    'committee' => 'Commissie',
                    'avc' => 'ALV-Commissie',
                    'avw' => 'ALV-Werkgroep',
                    'kcc' => 'KCC',
                    'fraternity' => 'Dispuut',
                    'rva' => 'RvA',
                ],
            ],
        ]);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Naam',
            ],
        ]);

        $this->add([
            'name' => 'abbr',
            'type' => Text::class,
            'options' => [
                'label' => 'Afkorting',
            ],
        ]);

        // Is this possible with a factory?
        $this->add([
            'name' => 'members',
            'type' => CollectionWithErrors::class,
            'options' => [
                'label' => 'Members',
                'count' => 2,
                'allow_add' => true,
                'should_create_template' => true,
                'target_element' => $function,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Richt op',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 128,
                        ],
                    ],
                ],
            ],

            'abbr' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],

            'members' => [
                'continue_if_empty' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                ],
            ],
        ];
    }
}
