<?php

namespace Database\Form;

use Application\Model\Enums\GenderTypes;
use Laminas\Filter\ToNull;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Digits;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class MemberEdit extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'lastName',
            'type' => 'text',
            'options' => [
                'label' => 'Achternaam',
            ],
        ]);

        $this->add([
            'name' => 'middleName',
            'type' => 'text',
            'options' => [
                'label' => 'Tussenvoegsels',
            ],
        ]);

        $this->add([
            'name' => 'initials',
            'type' => 'text',
            'options' => [
                'label' => 'Voorletter(s)',
            ],
        ]);

        $this->add([
            'name' => 'firstName',
            'type' => 'text',
            'options' => [
                'label' => 'Voornaam',
            ],
        ]);

        $this->add([
            'name' => 'gender',
            'type' => 'radio',
            'options' => [
                'label' => 'Geslacht',
                'value_options' => [
                    GenderTypes::Male->value => 'Man',
                    GenderTypes::Female->value => 'Vrouw',
                    GenderTypes::Other->value => 'Anders',
                ],
            ],
        ]);

        $this->add([
            'name' => 'tueUsername',
            'type' => 'text',
            'options' => [
                'label' => 'TU/e-gebruikersnaam',
            ],
        ]);

        $this->add([
            'name' => 'email',
            'type' => 'email',
            'options' => [
                'label' => 'Email-adres',
            ],
        ]);

        $this->add([
            'name' => 'birth',
            'type' => 'date',
            'options' => [
                'label' => 'Geboortedatum',
            ],
        ]);

        $this->add([
            'name' => 'paid',
            'type' => 'text',
            'options' => [
                'label' => 'Betaald (hoe veel)',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
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
