<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

class UserEdit extends Form implements InputFilterProviderInterface
{

    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'password',
            'type' => 'password',
            'options' => [
                'label' => 'Wachtwoord'
            ]
        ]);

        $this->add([
            'name' => 'password_verify',
            'type' => 'password',
            'options' => [
                'label' => 'Controleer wachtwoord'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Maak gebruiker aan'
            ]
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return [
            'password' => [
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
            'password_verify' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'identical',
                        'options' => [
                            'token' => 'password'
                        ]
                    ]
                ]
            ]
        ];
    }
}
