<?php

namespace Database\Form;

use Application\Model\Enums\AddressTypes;
use Database\Form\Fieldset\Address as AddressFieldset;
use DateInterval;
use DateTime;
use Exception;
use Laminas\Filter\ToNull;
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
use Laminas\I18n\Filter\Alnum;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Validator\{
    Callback,
    Iban,
    Identical,
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
                'label' => $translator->translate('Achternaam'),
            ],
        ]);

        $this->add([
            'name' => 'middleName',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Tussenvoegsels'),
            ],
        ]);

        $this->add([
            'name' => 'initials',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Voorletter(s)'),
            ],
        ]);

        $this->add([
            'name' => 'firstName',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Voornaam'),
            ],
        ]);

        $this->add([
            'name' => 'tueUsername',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('TU/e-gebruikersnaam'),
            ],
        ]);

        $this->add([
            'name' => 'study',
            'type' => Select::class,
            'options' => [
                'label' => $translator->translate('Studie'),
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
                'empty_option' => $translator->translate('Selecteer een studie'),
            ],
        ]);

        $this->add([
            'name' => 'email',
            'type' => Email::class,
            'options' => [
                'label' => $translator->translate('Email-adres'),
            ],
        ]);

        $this->add([
            'name' => 'birth',
            'type' => Date::class,
            'options' => [
                'label' => $translator->translate('Geboortedatum'),
            ],
        ]);


        $student = clone $address;
        $student->setName('address');
        $student->get('type')->setValue(AddressTypes::Student->value);
        $this->add($student);

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
                'label' => $translator->translate('Plaats van ondertekening'),
            ],
        ]);

        $this->add([
            'name' => 'agreediban',
            'type' => Checkbox::class,
        ]);

        $this->add([
            'name' => 'agreed',
            'type' => Checkbox::class,
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $translator->translate('Schrijf in'),
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
                                    'Weet je zeker dat je jonger bent dan 10 jaar?',
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            'iban' => [
                'required' => false,
                'validators' => [
                    ['name' => Iban::class],
                ],
                'filters' => [
                    ['name' => Alnum::class],
                    ['name' => ToNull::class],
                ],
            ],
            'signature' => [
                'required' => false,
                'filters' => [
                    ['name' => ToNull::class],
                ],
            ],
            'signatureLocation' => [
                'required' => false,
                'filters' => [
                    ['name' => ToNull::class],
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
                                'notSame' => $this->translator->translate('Je moet de voorwaarden accepteren!'),
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
                                'regexNotMatch' => $this->translator->translate(
                                    'Je TU/e-gebruikersnaam ziet er uit als sYYxxxx of als YYYYxxxx.',
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
