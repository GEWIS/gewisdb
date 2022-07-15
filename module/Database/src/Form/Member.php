<?php

namespace Database\Form;

use Application\Model\Enums\AddressTypes;
use Application\Model\Enums\GenderTypes;
use Laminas\Filter\ToNull;
use Laminas\Form\Form;
use Laminas\I18n\Filter\Alnum;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\Validator\Iban;
use Laminas\Validator\Identical;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class Member extends Form implements InputFilterProviderInterface
{
    /**
     * Lists
     */
    protected $lists;

    /**
     * Translator.
     */
    protected $translator;

    public function __construct(Fieldset\Address $address, Translator $translator)
    {
        parent::__construct();
        $this->translator = $translator;

        $this->add([
            'name' => 'lastName',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Achternaam'),
            ],
        ]);

        $this->add([
            'name' => 'middleName',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Tussenvoegsels'),
            ],
        ]);

        $this->add([
            'name' => 'initials',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Voorletter(s)'),
            ],
        ]);

        $this->add([
            'name' => 'firstName',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Voornaam'),
            ],
        ]);

        $this->add([
            'name' => 'gender',
            'type' => 'radio',
            'options' => [
                'value_options' => [
                    GenderTypes::Male->value => $translator->translate('Man'),
                    GenderTypes::Female->value => $translator->translate('Vrouw'),
                    GenderTypes::Other->value => $translator->translate('Anders'),
                ],
                'label' => $translator->translate('Geslacht'),
            ],
        ]);

        $this->add([
            'name' => 'tueUsername',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('TU/e-gebruikersnaam'),
            ],
        ]);

        $this->add([
            'name' => 'study',
            'type' => 'select',
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
            'type' => 'email',
            'options' => [
                'label' => $translator->translate('Email-adres'),
            ],
        ]);

        $this->add([
            'name' => 'birth',
            'type' => 'date',
            'options' => [
                'label' => $translator->translate('Geboortedatum'),
            ],
        ]);


        $student = clone $address;
        $student->setName('studentAddress');
        $student->get('type')->setValue(AddressTypes::Student->value);
        $this->add($student);

        $this->add([
            'name' => 'iban',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('IBAN'),
            ],
        ]);

        $this->add([
            'name' => 'signature',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'signatureLocation',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Plaats van ondertekening'),
            ],
        ]);

        $this->add([
            'name' => 'agreediban',
            'type' => 'checkbox',
        ]);

        $this->add([
            'name' => 'agreed',
            'type' => 'checkbox',
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Schrijf in'),
            ],
        ]);
    }

    /**
     * Set the mailing lists.
     *
     * @param array $lists Array of mailing lists
     */
    public function setLists($lists)
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
     *
     * @return array of mailing lists
     */
    public function getLists()
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
            'iban' => [
                'validators' => [
                    ['name' => Iban::class],
                ],
                'filters' => [
                    ['name' => Alnum::class],
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
                'required' => false,
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^(s\d{6}|\d{8})$/',
                            'messages' => [
                                'regexNotMatch' => $this->translator->translate('Je TU/e-gebruikersnaam ziet er uit als sXXXXXX of als YYYYXXXX.'),
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
}
