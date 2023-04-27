<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\Password;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class Login extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'login',
            'type' => Text::class,
            'options' => ['label' => 'Login'],
        ]);

        $this->add([
            'name' => 'password',
            'type' => Password::class,
            'options' => ['label' => 'Password'],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => ['value' => 'Login'],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'login' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 32,
                        ],
                    ],
                    [
                        'name' => Regex::class,
                        'options' => ['pattern' => '/^[a-zA-Z0-9]*$/'],
                    ],
                ],
            ],
            'password' => ['required' => true],
        ];
    }
}
