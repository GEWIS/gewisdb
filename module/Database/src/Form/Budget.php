<?php

namespace Database\Form;

use Database\Model\SubDecision;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Date;
use Laminas\Validator\InArray;
use Laminas\Validator\StringLength;

class Budget extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting, Fieldset\Member $member)
    {
        parent::__construct($meeting);

        $this->add([
            'name' => 'type',
            'type' => 'select',
            'options' => [
                'label' => 'Begroting / Afrekening',
                'value_options' => [
                    'budget' => 'Begroting',
                    'reckoning' => 'Afrekening',
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
            'name' => 'date',
            'type' => 'date',
            'options' => [
                'label' => 'Datum begroting / afrekening',
            ],
        ]);

        $member->setName('author');
        $member->setLabel('Auteur');
        $this->add($member);

        $this->add([
            'name' => 'version',
            'type' => 'text',
            'options' => [
                'label' => 'Versie',
            ],
        ]);

        $this->add([
            'name' => 'approve',
            'type' => 'radio',
            'options' => [
                'label' => 'Goedkeuren / Afkeuren',
                'value_options' => [
                    'true' => 'Goedkeuren',
                    'false' => 'Afkeuren',
                ],
                // forward compatability with ZF 2.3, doesn't actually do anything right now
                'disable_inarray_validator' => true,
            ],
        ]);

        $this->add([
            'name' => 'changes',
            'type' => 'radio',
            'options' => [
                'label' => 'Wijzigingen',
                'value_options' => [
                    'true' => 'Met wijzigingen',
                    'false' => 'Zonder wijzigingen',
                ],
                // forward compatability with ZF 2.3, doesn't actually do anything right now
                'disable_inarray_validator' => true,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Verzend',
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'type' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [
                                'budget',
                                'reckoning',
                            ],
                        ],
                    ],
                ],
            ],
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 255,
                        ],
                    ],
                ],
            ],
            'date' => [
                'required' => true,
                'validators' => [
                    ['name' => Date::class],
                ],
            ],
            // TODO: update author check
            'version' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            // Boolean values have no filter. The form will make sure that it will be casted to true or false
            // And because of the filters the filter is unable to detect if a value is set.
            'approve' => [
                'required' => true,
                'allow_empty' => false,
                'fallback_value' => false,
            ],
            'changes' => [
                'required' => true,
                'allow_empty' => false,
                'fallback_value' => false,
            ],
        ];
    }
}
