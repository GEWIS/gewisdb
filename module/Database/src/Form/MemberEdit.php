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

        $this->add(array(
            'name' => 'lastName',
            'type' => 'text',
            'options' => array(
                'label' => 'Achternaam'
            )
        ));

        $this->add(array(
            'name' => 'middleName',
            'type' => 'text',
            'options' => array(
                'label' => 'Tussenvoegsels'
            )
        ));

        $this->add(array(
            'name' => 'initials',
            'type' => 'text',
            'options' => array(
                'label' => 'Voorletter(s)'
            )
        ));

        $this->add(array(
            'name' => 'firstName',
            'type' => 'text',
            'options' => array(
                'label' => 'Voornaam'
            )
        ));

        $this->add(array(
            'name' => 'gender',
            'type' => 'radio',
            'options' => array(
                'label' => 'Geslacht',
                'value_options' => array(
                    GenderTypes::Male->value => 'Man',
                    GenderTypes::Female->value => 'Vrouw',
                    GenderTypes::Other->value => 'Anders'
                )
            )
        ));

        $this->add(array(
            'name' => 'tueUsername',
            'type' => 'text',
            'options' => array(
                'label' => 'TU/e-gebruikersnaam'
            )
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => 'Email-adres'
            )
        ));

        $this->add(array(
            'name' => 'birth',
            'type' => 'date',
            'options' => array(
                'label' => 'Geboortedatum'
            )
        ));

        $this->add(array(
            'name' => 'paid',
            'type' => 'text',
            'options' => array(
                'label' => 'Betaald (hoe veel)'
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Wijzig gegevens'
            )
        ));
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
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
            'paid' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Digits::class)
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
                                'regexNotMatch' => 'Een TU/e-gebruikersnaam ziet er uit als sXXXXXX of als YYYYXXXX.'
                            )
                        )
                    )
                ),
                'filters' => array(
                    array('name' => ToNull::class)
                )
            ),
            'email' => array(
                'required' => false,
                'validators' => array(
                    array(
                        'name' => EmailAddress::class,
                    )
                ),
                'filters' => array(
                    array('name' => ToNull::class)
                )
            )
        );
    }
}
