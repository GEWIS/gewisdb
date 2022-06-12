<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class MailingList extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Naam'
            ]
        ]);

        $this->add([
            'name' => 'description',
            'type' => 'textarea',
            'options' => [
                'label' => 'Beschrijving (nederlands)'
            ]
        ]);

        $this->add([
            'name' => 'enDescription',
            'type' => 'textarea',
            'options' => [
                'label' => 'Beschrijving (engels)'
            ]
        ]);

        $this->add([
            'name' => 'onForm',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Op inschrijfformulier'
            ]
        ]);
        $this->get('onForm')->setChecked(true);

        $this->add([
            'name' => 'defaultSub',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Standaard ingeschreven'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Voeg lijst toe'
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
                            'max' => 64
                        ]
                    ]
                ]
            ],
            'description' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 10
                        ]
                    ]
                ]
            ],
            'enDescription' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 10
                        ]
                    ]
                ]
            ],
        ];
    }
}
