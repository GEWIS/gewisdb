<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

class Login extends Form implements InputFilterProviderInterface
{

    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'login',
            'type' => 'text',
            'options' => [
                'label' => 'Login'
            ]
        ]);

        $this->add([
            'name' => 'password',
            'type' => 'password',
            'options' => [
                'label' => 'Password'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Login'
            ]
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return [
            'login' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 3,
                            'max' => 32
                        ]
                    ],
                    [
                        'name' => 'regex',
                        'options' => [
                            'pattern' => '/^[a-zA-Z0-9]*$/'
                        ]
                    ]
                ]
            ],
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
            ]
        ];
    }
}
