<?php

namespace Database\Form;

use Zend\InputFilter\InputFilter;
use Database\Model\SubDecision;

class Budget extends AbstractDecision
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
                    'reckoning' => 'Afrekening'
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
            'name' => 'date',
            'type' => 'date',
            'options' => [
                'label' => 'Datum begroting / afrekening'
            ]
        ]);

        $member->setName('author');
        $member->setLabel('Auteur');
        $this->add($member);

        $this->add([
            'name' => 'version',
            'type' => 'text',
            'options' => [
                'label' => 'Versie'
            ]
        ]);

        $this->add([
            'name' => 'approve',
            'type' => 'radio',
            'options' => [
                'label' => 'Goedkeuren / Afkeuren',
                'value_options' => [
                    'true' => 'Goedkeuren',
                    'false' => 'Afkeuren'
                ],
                // forward compatability with ZF 2.3, doesn't actually do anything right now
                'disable_inarray_validator' => true
            ]
        ]);

        $this->add([
            'name' => 'changes',
            'type' => 'radio',
            'options' => [
                'label' => 'Wijzigingen',
                'value_options' => [
                    'true' => 'Met wijzigingen',
                    'false' => 'Zonder wijzigingen'
                ],
                // forward compatability with ZF 2.3, doesn't actually do anything right now
                'disable_inarray_validator' => true
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Verzend'
            ]
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'type',
            'required' => true,
            'validators' => [
                [
                    'name' => 'in_array',
                    'options' => [
                        'haystack' => [
                            'budget',
                            'reckoning'
                        ]
                    ]
                ]
            ]
        ]);

        $filter->add([
            'name' => 'name',
            'required' => true,
            'validators' => [
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 3,
                        'max' => 255
                    ]
                ]
            ]
        ]);

        $filter->add([
            'name' => 'date',
            'required' => true,
            'validators' => [
                ['name' => 'date']
            ]
        ]);

        // TODO: update author check

        $filter->add([
            'name' => 'version',
            'required' => true,
            'validators' => [
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 1,
                        'max' => 32
                    ]
                ]
            ]
        ]);

        // Boolean values have no filter. The form will make sure that it will be casted to true or false
        // And because of the filters the filter is unable to detect if a value is set.
        $filter->add([
            'name' => 'approve',
            'required' => true,
            'allow_empty' => false,
            'fallback_value' => false,

        ]);

        $filter->add([
            'name' => 'changes',
            'required' => true,
            'allow_empty' => false,
            'fallback_value' => false,
        ]);

        $this->setInputFilter($filter);
    }
}
