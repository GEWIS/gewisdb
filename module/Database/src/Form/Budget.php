<?php

namespace Database\Form;

use Laminas\Form\Element\{
    Date,
    Radio,
    Select,
    Submit,
    Text,
};
use Database\Form\Fieldset\{
    Meeting as MeetingFieldset,
    Member as MemberFieldset,
};
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    Date as DateValidator,
    InArray,
    StringLength,
};

class Budget extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        MeetingFieldset $meeting,
        MemberFieldset $member,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'type',
            'type' => Select::class,
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
            'type' => Text::class,
            'options' => [
                'label' => 'Naam',
            ],
        ]);

        $this->add([
            'name' => 'date',
            'type' => Date::class,
            'options' => [
                'label' => 'Datum begroting / afrekening',
            ],
        ]);

        $member->setName('author');
        $member->setLabel('Auteur');
        $this->add($member);

        $this->add([
            'name' => 'version',
            'type' => Text::class,
            'options' => [
                'label' => 'Versie',
            ],
        ]);

        $this->add([
            'name' => 'approve',
            'type' => Radio::class,
            'options' => [
                'label' => 'Goedkeuren / Afkeuren',
                'value_options' => [
                    '1' => 'Goedkeuren',
                    '0' => 'Afkeuren',
                ],
            ],
        ]);

        $this->add([
            'name' => 'changes',
            'type' => Radio::class,
            'options' => [
                'label' => 'Wijzigingen',
                'value_options' => [
                    '1' => 'Met wijzigingen',
                    '0' => 'Zonder wijzigingen',
                ],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
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
                    ['name' => DateValidator::class],
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
