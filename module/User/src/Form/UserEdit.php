<?php

namespace User\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Identical;
use Laminas\Validator\StringLength;

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
                'value' => 'Wijzig gebruiker'
            ]
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'password' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
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
                        'name' => Identical::class,
                        'options' => [
                            'token' => 'password'
                        ]
                    ]
                ]
            ]
        ];
    }
}
