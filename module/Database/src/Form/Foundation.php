<?php

namespace Database\Form;

use Database\Form\Fieldset\CollectionWithErrors;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

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
                    'rva' => 'RvA',
                ],
            ],
        ]);

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Naam',
            ],
        ]);

        $this->add([
            'name' => 'abbr',
            'type' => 'text',
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
            'type' => 'submit',
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
