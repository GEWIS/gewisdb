<?php

namespace Database\Form;

use Database\Model\Address;
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

        $this->add(array(
            'name' => 'lastName',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Achternaam')
            )
        ));

        $this->add(array(
            'name' => 'middleName',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Tussenvoegsels')
            )
        ));

        $this->add(array(
            'name' => 'initials',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Voorletter(s)')
            )
        ));

        $this->add(array(
            'name' => 'firstName',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Voornaam')
            )
        ));

        $this->add(array(
            'name' => 'gender',
            'type' => 'radio',
            'options' => array(
                'value_options' => array(
                    'm' => $translator->translate('Man'),
                    'f' => $translator->translate('Vrouw'),
                    'o' => $translator->translate('Anders'),
                ),
                'label' => $translator->translate('Geslacht'),
            )
        ));

        $this->add(array(
            'name' => 'tueUsername',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('TU/e-gebruikersnaam')
            )
        ));

        $this->add(array(
            'name' => 'study',
            'type' => 'select',
            'options' => array(
                'label' => $translator->translate('Studie'),
                'value_options' => array(
                    'bachelor' => array(
                        'label' => 'Bachelor',
                        'options' => array(
                            'Bachelor Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Bachelor Applied Mathematics' => 'Applied Mathematics'
                        )
                    ),
                    'master' => array(
                        'label' => 'Master',
                        'options' => array(
                            'Master Industrial and Applied Mathematics' => 'Industrial and Applied Mathematics',
                            'Master Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Master Data Science in Engineering' => 'Data Science in Engineering',
                            'Master Information Security Technology' => 'Information Security Technology',
                            'Master Embedded Systems' => 'Embedded Systems',
                            'Master Science Education and Communication' => 'Science Education and Communication'
                        )
                    ),
                    'other' => array(
                        'label' => 'Other',
                        'options' => array(
                            'Other' => 'Other'
                        )
                    ),
                ),
                'empty_option' => $translator->translate('Selecteer een studie')
            )
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => $translator->translate('Email-adres')
            )
        ));

        $this->add(array(
            'name' => 'birth',
            'type' => 'date',
            'options' => array(
                'label' => $translator->translate('Geboortedatum')
            )
        ));


        $student = clone $address;
        $student->setName('studentAddress');
        $student->get('type')->setValue(Address::TYPE_STUDENT);
        $this->add($student);

        $this->add([
            'name' => 'iban',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('IBAN')
            ]
        ]);

        $this->add(array(
            'name' => 'signature',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'signatureLocation',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Plaats van ondertekening')
            )
        ));

        $this->add(array(
            'name' => 'agreediban',
            'type' => 'checkbox'
        ));

        $this->add(array(
            'name' => 'agreed',
            'type' => 'checkbox'
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Schrijf in')
            )
        ));
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
            $this->add(array(
                'name' => 'list-' . $list->getName(),
                'type' => 'checkbox',
                'options' => array(
                    'label' => '<strong>' . $list->getName() . '</strong> ' . $desc
                )
            ));
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
        return array(
            'lastName' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
            'middleName' => array(
                'required' => false,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
            'initials' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 1,
                            'max' => 16
                        )
                    )
                )
            ),
            'firstName' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 1,
                            'max' => 32
                        )
                    )
                )
            ),
            'iban' => [
                'validators' => [
                    ['name' => Iban::class]
                ],
                'filters' => [
                    ['name' => Alnum::class]
                ]
            ],
            'agreed' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => Identical::class,
                        'options' => array(
                            'token' => '1',
                            'messages' => array(
                                'notSame' => $this->translator->translate('Je moet de voorwaarden accepteren!')
                            )
                        )
                    )
                )
            ),
            'tueUsername' => array(
                'required' => false,
                'validators' => array(
                    array(
                        'name' => Regex::class,
                        'options' => array(
                            'pattern' => '/^(s\d{6}|\d{8})$/',
                            'messages' => array(
                                'regexNotMatch' => $this->translator->translate('Je TU/e-gebruikersnaam ziet er uit als sXXXXXX of als YYYYXXXX.')
                            )
                        )
                    )
                ),
                'filters' => array(
                    array('name' => ToNull::class)
                )
            )
        );
    }
}
