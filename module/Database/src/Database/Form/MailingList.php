<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class MailingList extends Form
    implements InputFilterProviderInterface
{

    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Naam'
            )
        ));

        $this->add(array(
            'name' => 'description',
            'type' => 'text',
            'options' => array(
                'label' => 'Beschrijving'
            )
        ));

        $this->add(array(
            'name' => 'onForm',
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Op inschrijfformulier',
                'checked' => true
            )
        ));

        $this->add(array(
            'name' => 'default',
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Standaard ingeschreven'
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Voeg lijst toe'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'name' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 64
                        )
                    )
                )
            ),
            'description' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 10
                        )
                    )
                )
            )
        );
    }
}
