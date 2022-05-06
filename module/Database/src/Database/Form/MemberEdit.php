<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

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
                    'm' => 'Man',
                    'f' => 'Vrouw',
                    'o' => 'Anders'
                )
            )
        ));

        $this->add(array(
            'name' => 'tuenumber',
            'type' => 'number',
            'options' => array(
                'label' => 'TU/e nummer'
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
    public function getInputFilterSpecification()
    {
        return array(
            'lastName' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
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
                        'name' => 'string_length',
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
                        'name' => 'string_length',
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
                        'name' => 'string_length',
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
                    array('name' => 'digits')
                )
            ),
            'tuenumber' => array(
                'required' => false,
                'validators' => array(
                    array('name' => 'digits')
                )
            )
        );
    }
}
