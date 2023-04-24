<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\{
    Password,
    Submit,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    Identical,
    StringLength,
};

class UserEdit extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'password',
            'type' => Password::class,
            'options' => [
                'label' => 'Wachtwoord',
            ],
        ]);

        $this->add([
            'name' => 'password_verify',
            'type' => Password::class,
            'options' => [
                'label' => 'Controleer wachtwoord',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Wijzig gebruiker',
            ],
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
                            'min' => 10,
                        ],
                    ],
                ],
            ],
            'password_verify' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Identical::class,
                        'options' => [
                            'token' => 'password',
                        ],
                    ],
                ],
            ],
        ];
    }
}
