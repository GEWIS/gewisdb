<?php

namespace Api\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

class ApiKey extends Form
    implements InputFilterProviderInterface
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
            'name' => 'webhook',
            'type' => 'text',
            'options' => [
                'label' => 'Webhook URL'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Maak API key'
            ]
        ]);
        $this->get('submit')->setLabel('Maak API key');
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
                            'max' => 32
                        ]
                    ]
                ]
            ],
            'webhook' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 10,
                            'max' => 255
                        ]
                    ],
                    [
                        'name' => 'uri',
                        'options' => [
                            'allowRelative' => false
                        ]
                    ],
                ]
            ]
        ];
    }
}
