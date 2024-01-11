<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\Password;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Identical;
use Laminas\Validator\StringLength;

/**
 * @phpstan-type UserEditFormType = array{
 *  password: string,
 *  password_verify: string,
 * }
 * @extends Form<UserEditFormType>
 */
class UserEdit extends Form implements InputFilterProviderInterface
{
    private bool $passwordNeeded = true;

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
                'required' => $this->passwordNeeded,
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
                'required' => $this->passwordNeeded,
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

    public function bind(
        object $object,
        int $flags = Form::VALUES_NORMALIZED,
    ): void {
        if ($object->isLocal()) {
            return;
        }

        $this->passwordNeeded = false;
        $this->get('password')->setAttribute('disabled', true);
        $this->get('password_verify')->setAttribute('disabled', true);
    }
}
