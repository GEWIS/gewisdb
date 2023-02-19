<?php

namespace Database\Form;

use Application\Model\Enums\AddressTypes;
use Database\Form\Fieldset\Address as AddressFieldset;
use DateInterval;
use DateTime;
use Exception;
use Laminas\I18n\Filter\Alnum;
use Laminas\Filter\{
    StringToUpper,
    StringTrim,
    ToNull,
};
use Laminas\Form\Element\{
    Checkbox,
    Date,
    Email,
    Hidden,
    Select,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Validator\{
    Callback,
    Iban,
    Identical,
    NotEmpty,
    Regex,
    StringLength,
};

class Member extends Form implements InputFilterProviderInterface
{
    protected array $lists;

    public function __construct(
        AddressFieldset $address,
        protected readonly MvcTranslator $translator,
    ) {
        parent::__construct();

        $this->add([
            'name' => 'lastName',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Last Name'),
            ],
        ]);

        $this->add([
            'name' => 'middleName',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Last Name Prepositional Particle'),
            ],
        ]);

        $this->add([
            'name' => 'initials',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Initial(s)'),
            ],
        ]);

        $this->add([
            'name' => 'firstName',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('First Name'),
            ],
        ]);

        $this->add([
            'name' => 'tueUsername',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('TU/e-username'),
            ],
        ]);

        $this->add([
            'name' => 'study',
            'type' => Select::class,
            'options' => [
                'label' => $translator->translate('Study'),
                'value_options' => [
                    'bachelor' => [
                        'label' => 'Bachelor',
                        'options' => [
                            'Bachelor Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Bachelor Applied Mathematics' => 'Applied Mathematics',
                        ],
                    ],
                    'master' => [
                        'label' => 'Master',
                        'options' => [
                            'Master Industrial and Applied Mathematics' => 'Industrial and Applied Mathematics',
                            'Master Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Master Data Science in Engineering' => 'Data Science in Engineering',
                            'Master Information Security Technology' => 'Information Security Technology',
                            'Master Embedded Systems' => 'Embedded Systems',
                            'Master Science Education and Communication' => 'Science Education and Communication',
                        ],
                    ],
                    'other' => [
                        'label' => 'Other',
                        'options' => [
                            'Other' => 'Other',
                        ],
                    ],
                ],
                'empty_option' => $translator->translate('Select a study'),
            ],
        ]);

        $this->add([
            'name' => 'email',
            'type' => Email::class,
            'options' => [
                'label' => $translator->translate('E-mail Address'),
            ],
        ]);

        $this->add([
            'name' => 'birth',
            'type' => Date::class,
            'options' => [
                'label' => $translator->translate('Birthdate'),
            ],
        ]);


        $student = clone $address;
        $student->setName('address');
        $student->get('type')->setValue(AddressTypes::Student->value);
        $this->add($student);

        if (DATABASE_REQUIRE_IBAN) {
            $this->add([
                'name' => 'iban',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('IBAN'),
                ],
            ]);

            $this->add([
                'name' => 'signature',
                'type' => Hidden::class,
            ]);

            $this->add([
                'name' => 'signatureLocation',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Place of Signing'),
                ],
            ]);

            $this->add([
                'name' => 'agreediban',
                'type' => Checkbox::class,
            ]);
        }

        $this->add([
            'name' => 'agreed',
            'type' => Checkbox::class,
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $translator->translate('Subscribe'),
            ],
        ]);
    }

    /**
     * Set the mailing lists.
     */
    public function setLists(array $lists): void
    {
        $this->lists = $lists;
        foreach ($this->lists as $list) {
            $desc = $list->getNlDescription();

            if ($this->translator->getLocale() == 'en') {
                $desc = $list->getEnDescription();
            }

            $this->add([
                'name' => 'list-' . $list->getName(),
                'type' => 'checkbox',
                'options' => [
                    'label' => '<strong>' . $list->getName() . '</strong> ' . $desc,
                ],
            ]);

            if ($list->getDefaultSub()) {
                $this->get('list-' . $list->getName())->setChecked(true);
            }
        }
    }

    /**
     * Get the mailing lists.
     */
    public function getLists(): array
    {
        return $this->lists;
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        $filter = [
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
                            'min' => 1,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'birth' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value) {
                                return $this->isOldEnough($value);
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Are you sure that you are younger than 10 years?',
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            'agreed' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Identical::class,
                        'options' => [
                            'token' => '1',
                            'messages' => [
                                Identical::NOT_SAME => $this->translator->translate(
                                    'You have to accept the terms!',
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            'tueUsername' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^(s\d{6}|\d{8})$/',
                            'messages' => [
                                Regex::NOT_MATCH => $this->translator->translate(
                                    'Your TU/e-username should look like sYYxxxx or YYYYxxxx.',
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => ToNull::class],
                ],
            ],
        ];

        if (DATABASE_REQUIRE_IBAN) {
            $filter += [
                'iban' => [
                    'required' => true,
                    'validators' => [
                        [
                            'name' => Iban::class,
                            'options' => [
                                'allow_non_sepa' => false,
                            ],
                        ],
                    ],
                    'filters' => [
                        ['name' => Alnum::class],
                        ['name' => StringToUpper::class],
                    ],
                ],
                'signature' => [
                    'required' => true,
                    'validators' => [
                        [
                            'name' => NotEmpty::class,
                            'options' => [
                                'messages' => [
                                    NotEmpty::IS_EMPTY => $this->translator->translate('Signature is required!'),
                                ],
                            ],
                        ],
                    ],
                ],
                'signatureLocation' => [
                    'required' => true,
                    'filters' => [
                        ['name' => StringTrim::class],
                    ],
                ],
                'agreediban' => [
                    'required' => true,
                    'validators' => [
                        [
                            'name' => Identical::class,
                            'options' => [
                                'token' => '1',
                                'messages' => [
                                    Identical::NOT_SAME => $this->translator->translate(
                                        'Please accept the conditions for payment through SEPA Direct Debit',
                                    ),
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $filter;
    }

    private function isOldEnough(string $value): bool
    {
        try {
            $longTimeAgo = (new DateTime('now'))->sub(new DateInterval('P10Y'));

            return (new DateTime($value)) < $longTimeAgo;
        } catch (Exception $e) {
            return false;
        }
    }
}
