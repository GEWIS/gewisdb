<?php

namespace Database\Form;

use Laminas\Filter\ToNull;
use Laminas\Form\Element\{
    Checkbox,
    Date,
    Email,
    Radio,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    Digits,
    EmailAddress,
    Regex,
    StringLength,
};

class MemberEdit extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'lastName',
            'type' => Text::class,
            'options' => [
                'label' => 'Achternaam',
            ],
        ]);

        $this->add([
            'name' => 'middleName',
            'type' => Text::class,
            'options' => [
                'label' => 'Tussenvoegsels',
            ],
        ]);

        $this->add([
            'name' => 'initials',
            'type' => Text::class,
            'options' => [
                'label' => 'Voorletter(s)',
            ],
        ]);

        $this->add([
            'name' => 'firstName',
            'type' => Text::class,
            'options' => [
                'label' => 'Voornaam',
            ],
        ]);

        $this->add([
            'name' => 'tueUsername',
            'type' => Text::class,
            'options' => [
                'label' => 'TU/e-gebruikersnaam',
            ],
        ]);

        $this->add([
            'name' => 'email',
            'type' => Email::class,
            'options' => [
                'label' => 'Email-adres',
            ],
        ]);

        $this->add([
            'name' => 'birth',
            'type' => Date::class,
            'options' => [
                'label' => 'Geboortedatum',
            ],
        ]);

        $this->add([
            'name' => 'paid',
            'type' => Text::class,
            'options' => [
                'label' => 'Betaald (hoe veel)',
            ],
        ]);

        $this->add([
            'name' => 'hidden',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Hide member',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Wijzig gegevens',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'lastName' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'middleName' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'initials' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 16,
                        ],
                    ],
                ],
            ],
            'firstName' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'paid' => [
                'required' => true,
                'validators' => [
                    ['name' => Digits::class],
                ],
            ],
            'tueUsername' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^(s\d{6}|\d{8})$/',
                            'messages' => [
                                'regexNotMatch' => 'Een TU/e-gebruikersnaam ziet er uit als sXXXXXX of als YYYYXXXX.',
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => ToNull::class],
                ],
            ],
            'email' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => EmailAddress::class,
                    ],
                ],
                'filters' => [
                    ['name' => ToNull::class],
                ],
            ],
        ];
    }
}
