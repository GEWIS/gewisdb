<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Validator\StudentNumber as StudentNumberValidator;
use Database\Model\Enums\Studies;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\StringLength;
use Override;

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
            'name' => 'studentNumber',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('TU/e student number'),
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
            'name' => 'study',
            'type' => Select::class,
            'options' => [
                'label' => $translator->translate('Study'),
                'value_options' => Studies::getValueOptions($translator, false, true),
                'empty_option' => $translator->translate('Select a study'),
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
    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [
            'lastName' => [
                'required' => true,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
            'studentNumber' => [
                'required' => false,
                'validators' => [
                    new StudentNumberValidator($this->translator),
                ],
                'filters' => [
                    ['name' => StringTrim::class],
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
