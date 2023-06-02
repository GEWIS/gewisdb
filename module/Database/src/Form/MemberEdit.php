<?php

declare(strict_types=1);

namespace Database\Form;

use Laminas\Filter\StringToLower;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Digits;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class MemberEdit extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'lastName',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Last Name'),
            ],
        ]);

        $this->add([
            'name' => 'middleName',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Last Name Prepositional Particle'),
            ],
        ]);

        $this->add([
            'name' => 'initials',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Initial(s)'),
            ],
        ]);

        $this->add([
            'name' => 'firstName',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('First Name'),
            ],
        ]);

        $this->add([
            'name' => 'tueUsername',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('TU/e-username'),
            ],
        ]);

        $this->add([
            'name' => 'email',
            'type' => Email::class,
            'options' => [
                'label' => $this->translator->translate('E-mail Address'),
            ],
        ]);

        $this->add([
            'name' => 'birth',
            'type' => Date::class,
            'options' => [
                'label' => $this->translator->translate('Birthdate'),
            ],
        ]);

        $this->add([
            'name' => 'paid',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Paid (Amount)'),
            ],
        ]);

        $this->add([
            'name' => 'hidden',
            'type' => Checkbox::class,
            'options' => [
                'label' => $this->translator->translate('Hide Member'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Change Data'),
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
                    [
                        'name' => Digits::class,
                    ],
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
                                'regexNotMatch' => $this->translator->translate(
                                    'A TU/e-username should look like sYYxxxx or YYYYxxxx.',
                                ),
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
                    ['name' => StringToLower::class],
                ],
            ],
        ];
    }
}
