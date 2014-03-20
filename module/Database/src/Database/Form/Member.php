<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class Member extends Form
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
                    'f' => 'Vrouw'
                )
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

        // TODO: accepteer voorwaarden

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Schrijf in'
            )
        ));
    }
}
