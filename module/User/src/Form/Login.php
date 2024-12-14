<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Password;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

use function getenv;
use function strlen;

class Login extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        $username = getenv('DEMO_CREDENTIALS_USERNAME', true);
        $password = getenv('DEMO_CREDENTIALS_PASSWORD', true);

        if (false === $username) {
            $username = '';
        }

        if (false === $password) {
            $password = '';
        }

        parent::__construct();

        if (strlen($password) > 0) {
            $this->add([
                'name' => 'login',
                'type' => Text::class,
                'options' => [
                    'label' => 'Login',
                ],
                'attributes' => [
                    'value' => $username,
                    'readonly' => true,
                ],
            ]);

            $this->add([
                'name' => 'password',
                'type' => Hidden::class,
                'options' => [
                    'label' => 'Password',
                ],
                'attributes' => [
                    'value' => $password,
                ],
            ]);
        } else {
            $this->add([
                'name' => 'login',
                'type' => Text::class,
                'options' => [
                    'label' => 'Login',
                ],
            ]);

            $this->add([
                'name' => 'password',
                'type' => Password::class,
                'options' => [
                    'label' => 'Password',
                ],
            ]);
        }

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Login',
            ],
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
            'password' => [
                'required' => true,
            ],
        ];
    }
}
