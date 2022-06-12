<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Database\Model\AddressModel;

class Address extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'country',
            'type' => 'text',
            'options' => [
                'label' => 'Land',
                'value' => 'netherlands'
            ]
        ]);

        $this->add([
            'name' => 'street',
            'type' => 'text',
            'options' => [
                'label' => 'Straat'
            ]
        ]);

        $this->add([
            'name' => 'number',
            'type' => 'text',
            'options' => [
                'label' => 'Huisnummer'
            ]
        ]);

        $this->add([
            'name' => 'postalCode',
            'type' => 'text',
            'options' => [
                'label' => 'Postcode'
            ]
        ]);

        $this->add([
            'name' => 'city',
            'type' => 'text',
            'options' => [
                'label' => 'Stad'
            ]
        ]);

        $this->add([
            'name' => 'phone',
            'type' => 'text',
            'options' => [
                'label' => 'Telefoonnummer'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Wijzig adres'
            ]
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return [
            'country' => [
                'required' => true,
                'filters' => [
                    ['name' => 'string_to_lower']
                ],
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
            'street' => [
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
            'number' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'regex',
                        'options' => [
                            'pattern' => '/^[0-9]+[a-zA-Z]*/'
                        ]
                    ]
                ]
            ],
            'postalCode' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 16
                        ]
                    ]
                ]
            ],
            'city' => [
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
            ]
            // TODO: phone number validation
        ];
    }
}
