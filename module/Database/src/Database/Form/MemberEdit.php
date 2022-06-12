<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class MemberEdit extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'lastName',
            'type' => 'text',
            'options' => [
                'label' => 'Achternaam'
            ]
        ]);

        $this->add([
            'name' => 'middleName',
            'type' => 'text',
            'options' => [
                'label' => 'Tussenvoegsels'
            ]
        ]);

        $this->add([
            'name' => 'initials',
            'type' => 'text',
            'options' => [
                'label' => 'Voorletter(s)'
            ]
        ]);

        $this->add([
            'name' => 'firstName',
            'type' => 'text',
            'options' => [
                'label' => 'Voornaam'
            ]
        ]);

        $this->add([
            'name' => 'gender',
            'type' => 'radio',
            'options' => [
                'label' => 'Geslacht',
                'value_options' => [
                    'm' => 'Man',
                    'f' => 'Vrouw',
                    'o' => 'Anders'
                ]
            ]
        ]);

        $this->add([
            'name' => 'tueUsername',
            'type' => 'text',
            'options' => [
                'label' => 'TU/e-gebruikersnaam'
            ]
        ]);

        $this->add([
            'name' => 'email',
            'type' => 'email',
            'options' => [
                'label' => 'Email-adres'
            ]
        ]);

        $this->add([
            'name' => 'birth',
            'type' => 'date',
            'options' => [
                'label' => 'Geboortedatum'
            ]
        ]);

        $this->add([
            'name' => 'paid',
            'type' => 'text',
            'options' => [
                'label' => 'Betaald (hoe veel)'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Wijzig gegevens'
            ]
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return [
            'lastName' => [
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
            'middleName' => [
                'required' => false,
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
            'initials' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 1,
                            'max' => 16
                        ]
                    ]
                ]
            ],
            'firstName' => [
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
            'paid' => [
                'required' => true,
                'validators' => [
                    ['name' => 'digits']
                ]
            ],
            'tueUsername' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'regex',
                        'options' => [
                            'pattern' => '/^(s\d{6}|\d{8})$/',
                            'messages' => [
                                'regexNotMatch' => 'Een TU/e-gebruikersnaam ziet er uit als sXXXXXX of als YYYYXXXX.'
                            ]
                        ]
                    ]
                ],
                'filters' => [
                    ['name' => 'tonull']
                ]
            ]
        ];
    }
}
